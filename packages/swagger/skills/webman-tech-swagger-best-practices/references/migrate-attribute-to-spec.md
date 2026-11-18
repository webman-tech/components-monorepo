# 从 classic attribute 迁移到 Spec 模式

本组件由 classic（`OpenApi\Attributes` + Generator/Processor）切到纯 Spec（`OpenApi\Spec` + Builder/Augmenter），是 major 破坏性升级。

**推荐路径：官方 Rector set 做机械迁移 → 手工补配置 → 与升级前的文档基线对比。不要用正则批量替换。**

理由：旧属性在 `Mode::SPEC` 下是**静默失效**——不抛异常、不写日志。operation 没被收集，文档里就没有该接口；`register_route` 下路由同样不注册（接口直接 404）。迁移完只剩 `GET /__example` 占位接口就是典型症状。写法对照表见 README「从旧模式迁移」。

## 0. 升级前必做

- 锁 `zircote/swagger-php: ^7.3`：官方迁移 set 跟随库版本、自身不承诺兼容，8.0 会随 classic 一起移除。
- 固定 OpenAPI 版本：classic 默认 3.0.0、spec 默认 3.1.0，两边都显式设 `openapi_version: '3.1.0'`（CLI 用 `--version`），否则对比结果没有意义。
- 保存升级前的文档作基线：`/openapi.json?generate=1`。
- 盘点：`git grep -l -F 'OpenApi\Attributes' -- '*.php'`。
- 装迁移工具：`composer require --dev rector/rector`。

## 1. 跑官方 Rector set

```bash
./vendor/bin/rector process <所有含属性的目录> \
    --config vendor/zircote/swagger-php/rector/set/classic-to-spec.php --dry-run
# diff 复核通过后去掉 --dry-run
```

- **目录要覆盖全部用到属性的文件**，不只是 `scan_path`：继承 `OpenApi\Attributes\*` 的类、`new` 出来的注解对象同样要迁移。
- **默认别名是 `OAS` 而不是 `OA`**：官方 `AliasSpecImportsRector` 会把 `use OpenApi\Attributes as OA;` 换成 `use OpenApi\Spec as OAS;`，并把所有 `#[OA\...]` 改成 `#[OAS\...]`。原因见下文「保持 `OA` 别名」。
- 本组件的 `x: [SchemaConstants::X_SCHEMA_REQUEST => ...]`、`@handle`、路由扩展配置**不会被改写**，可放心跑。
- `#[OA\Schema(schema: 'X')]` 的 `schema:`（以及 `parameter:` / `request:` / `securityScheme:`）会被原样保留，8.0 前仍生效但触发 deprecation → 统一换成 `component:`。
- 框架路由属性与 `ComponentsHolderRector` 冲突时，按官方指南配置 `ROUTE_ATTRIBUTES`，或直接 `withSkip([ComponentsHolderRector::class => ['*/Controller/*']])`。

set 会做：位置参数具名化（避免改名后参数错绑）、根节点 `OpenApi` 拆成同级 Info/Server/Tag、`security` → `Security\Requirement`、parameter style 折叠、`enum: X::class` 包成数组、`type: X::class` → `ref: X::class`、类型别名 `long` → `integer/int64`、类重命名、schema 关键字下移到 `schema: new Schema(...)`。

### 保持 `OA` 别名

官方刻意默认 `OAS`：一是 hybrid 文件里常有 `use OpenApi\Annotations as OA;`，`OA` 会撞名；二是 `OAS` 可以作为「已迁移」标记（`git grep -l 'as OAS'` 就能看迁移进度），8.0 把 `OpenApi\Spec` 改回 `OpenApi\Attributes` 时也不会和未迁移的旧 import 混淆。全量一次性迁移、且没有 hybrid 文件时，保留 `OA` 更省事。

**关键是不能用 `--config` 直接指向官方 set**（那个文件的配置是写死的），要在项目自己的 `rector.php` 里 `withSets()` 引入 set，再用 `withConfiguredRule()` 覆盖别名规则：

```php
<?php
// 项目根目录 rector.php
use OpenApi\Rector\AliasSpecImportsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withSets([__DIR__ . '/vendor/zircote/swagger-php/rector/set/classic-to-spec.php'])
    ->withConfiguredRule(AliasSpecImportsRector::class, [
        AliasSpecImportsRector::ALIAS => 'OA',
    ]);
```

```bash
./vendor/bin/rector process <所有含属性的目录> --dry-run   # 不传 --config，默认读 rector.php
# 复核通过后去掉 --dry-run
```

实测输出：`use OpenApi\Spec as OA;` + `#[OA\Operation\Get(...)]`，其余改写与默认 set 完全一致。若项目本来就有 `rector.php`，把这两行并进去即可。

如果已经用默认 `OAS` 跑完了，也可以事后全局替换：`OAS` 足够独特，把代码里的 `OAS\` 换成 `OA\`、import 行改成 `use OpenApi\Spec as OA;` 即可；官方规则注释本身也把这一步列为「收尾」。

## 2. Rector 不管的，手工补

| 项 | 处理 |
|---|---|
| 配置 `modify` / `generator_modify` | 删掉，统一为 `builder_modify(Builder): void` + `withSpecification`；完整示例见本 skill 的「快速开始」 |
| 自定义 Processor | 改成 augmenter（`PipeInterface<Specification>`，Resolve 组、Refs 之前），是重写不是改名 |
| docblock 注解 `@OA\Get` | 不处理，先转成 attribute 再跑 Rector |
| `PropertyInEnum` / `SwaggerHelper` | 已删；改用 `WebmanTech\DTO\Enums\RequestPropertyInEnum` 或原生 Spec 对象 |
| `auto_load_schema_classes` 显式配置过 | 会覆盖默认值，需加回 `BaseDTO::class`（用 Eloquent 再加 `Model::class`），否则 DTO 不再自动展开 |
| 一个方法堆多个 `#[Response]` / 多个 Operation | spec 会报 `Ambiguous merge` 并丢掉整个方法 → 用 `fan: OA\Fan::All` 或合并为 `#[OA\Operation(methods: [...])]` |
| `collectionFormat` / `additionalItems` / `dependencies` / GET 上的 `requestBody` | 无对应参数，加载时抛 `Unknown named parameter`，需手工删 |
| `Attributes\Query` / `Webhook` / `PathItem` / 构造函数上的属性 | 无一对一替换，手工改 |

动态配置的最小形态（Info / Server 从 `modify` 搬到这里）：

```php
'openapi_doc' => [
    'builder_modify' => function (OpenApi\Builder $builder): void {
        $builder->withSpecification(function (OpenApi\Specification $spec): void {
            $spec->info = new OpenApi\Spec\Info(title: config('app.name') . ' API', version: '1.0.0');
            $spec->add(new OpenApi\Spec\Server(url: route_url('/api')));
        });
    },
],
```

## 3. 顺手简化（改完就能删）

- DTO 里的字段级 `#[OA\Property]`：类型来自 PHP 类型、描述来自 PHPDoc、示例来自 `@example`、约束来自 `#[ValidationRules]`、必填来自非 nullable 且无默认值。
- 手写 `#[OA\Parameter]`：改用 `#[RequestPropertyInQuery]` / `Path` / `Header` / `Cookie` / `Json` / `Form` / `Body`。
- 方法上的 `tags:`：类级一个 `#[OA\Tag]` 即可。
- 手写 `responses:`：`x: [SchemaConstants::X_SCHEMA_REQUEST => Form::class . '@handle']` 一行出请求和响应（`handle()` 要有返回类型）。
- 逐接口的 `x-response-layout`：改全局 `response_layout_class`，个别接口用 `SchemaConstants::X_RESPONSE_LAYOUT => null` 关闭。

## 4. 验收（关键：不能只看「页面能打开」）

1. 先设 `register_route => false` 只验文档，`/openapi.json?generate=1` 与基线对比 **paths 数量、每个 path 的 parameters / requestBody / 200 schema**。
2. 文档只剩 `GET /__example` ⇒ 还有接口没被迁移；局部字段缺失优先查混写的旧 `#[OA\Property]`（属性还在但 `description` / `example` 会静默消失）。
3. 再打开 `register_route`，路由数应与升级前一致。
4. 抽查 query 参数 `in`、上传接口 `multipart/form-data`、多态 `oneOf`。
5. 收尾复查：`git grep -F 'OpenApi\Attributes' -- '*.php'` 应为零。

## 已知行为差异

- `@handle` 的 `handle()` 没有返回类型时不再报错，只跳过响应推导并保留默认 200 响应。
- 验证规则写入 description 改走 operation meta，不再经过 `x['validation-rules']`（内部实现，下游无需改代码）。
- 官方 spec 的 Cleanup 默认开启，但本组件默认关闭（`clean_unused_components_enable = false`）；显式开启后，只用于生成客户端代码、没有被任何 path 引用的组件会被清掉，需要保留就关掉它。

## 参考

- 官方迁移指南：http://zircote.com/swagger-php/guide/migrating-to-spec
- 官方 set：`vendor/zircote/swagger-php/rector/set/classic-to-spec.php`
