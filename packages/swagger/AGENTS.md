# Swagger 组件维护

使用 swagger-php `^7.3` 的纯 `Mode::SPEC`，模型为 `OpenApi\Spec`。旧 Attributes、Annotations、Generator/Analysis 扩展链不在兼容范围内。

## 目录和生成链路

`src/Plugin/<来源>/` 是插件边界：入口、Options、Augmenter、Translator 及专用辅助类放在同一来源目录。核心功能归 `Plugin/Core`，DTO、Eloquent、NativeEnum、WebmanRoute、WebmanHttp、SymfonyHttp 各自独立。依赖检测集中在 `Spec/DocumentBuilder`，来源插件只在对应依赖可用时加载；插件职责与可选依赖表见 README。

`src/Spec/` 保留构建器、ComponentNamer、SpecHelper、ListPosition 及跨插件共享的 Translator。AutoSchemaTranslator 为配置的基类和 trait 生成 Schema/Property，`.generated` meta 让 Eloquent 适配器区分自动属性与显式 OA 属性。UploadSchemaTranslator 由两个 HTTP 插件在同一 AttributeFactory 中共同注册，每个工厂只保留一个实例。

DocumentBuilder 先通过官方 `withPlugins()` 注册插件，再执行 `builder_modify`，允许应用覆盖默认配置。最后通过 `withSpecification(new Core\SpecificationContribution(...))` 注册依赖发现和空文档默认信息，使用户程序化添加的接口也能在 Resolver 前收集依赖。模式、版本、扫描源和命名策略属于 Builder，不放入插件 Hooks。

| 阶段 | 扩展 | 插入位置 |
|---|---|---|
| Specification contribution | Core/SpecificationContribution | 应用 hooks 后、Resolver 前 |
| Resolve | Core/OperationTags | 官方 Types 前，类/方法标签尚未 Merge |
| Resolve | Dto/SchemaAugmenter、CollectValidationRules | 官方 Properties 后、XSchema 展开前 |
| Resolve | Core/XSchemaAugmenter | 官方 Refs 前，新参数及引用参与后续解析 |
| Augment | DTO 验证规则描述、Enum 描述 | 官方 Docblocks 后 |
| Augment | WebmanRoute/RouteMetadata、Core/FinalizeAugmenter | 官方 Tags 前 |

所有 augmenter 实现 `PipeInterface<Specification>` 并显式声明 Group。通过官方节点定位执行顺序，插件不探测其他来源插件的实现类。ListPosition 为官方 TypedList 的 insert 提供 after 定位，缺少锚点时明确报错。

XSchemaAugmenter 实现官方 `AttributeFactoryAwareInterface`，在构建时从注入的工厂获取 UploadSchemaTranslator，复用最终上传类型配置，不额外维护注册表。新增 HTTP 集成只注册对应上传类，不在核心里判断框架类型。

WebmanRoute 插件只保留或清理路由元数据，通过 `RouteConfigDTO` 交换路由定义。Reader 与插件共用 `RouteConfigDTO::fromOperation()`，插件不依赖 Reader，也不写运行中的路由表。实际注册仍由 Swagger 和 common-utils 的路由适配器在启动期完成。

各插件无构造参数，支持官方类名及配置数组写法；有配置的插件通过本目录 Options 验证键名和类型。augmenter 只接收自身需要的设置，避免引入其他来源的配置。配置类继承本包 `DTO/BaseConfigDTO`，基础文档生成无需安装 DTO。

## 扩展约定

通过 `builder_modify(Builder): void` 配置官方 extension points；文档修改在 Specification 上完成，编译后直接序列化和缓存。

Spec 的 `Property` 通过 `schema: new OA\Schema(...)` 承载类型约束；组件名用 `component:`。大部分未设置字段为 null，description/example/default 仍可能使用 `Undefined::UNDEFINED`；读取时使用 `SpecHelper::value`，保留 0、false、空字符串和空数组。

Operation 不继承类级 Tag 时，在 `x` 中使用 `SchemaConstants::X_SKIP_CLASS_TAG => true`；`tags` 只放最终输出的业务标签，不能使用控制字符串代替扩展配置。

`SchemaDependency` 通过官方 `RefInterface`/`RefTrait` 声明临时引用；自定义 resolver 使用 `KeyResolverInterface::resolveKey()`。转换出的 Parameter、Header、二进制 Schema 保留来源 reflector，由官方 Augment 组的 Docblocks 统一补充描述、废弃标记和示例，尊重 builder 配置的 parser。保留 Undefined 与显式 null 的区别；不要额外创建 Docblocks 或重写用户显式的 example/default。

组件索引按桶延迟构建；一旦读取就是该桶当时的快照。DTO SchemaAugmenter 的索引仅查询已存在的映射目标，XSchemaAugmenter 复用 Schema 索引并单独维护新增参数的名称集合。引用通过 `getRefValue()` / `getRefKey()` 读取，带限定键的 Schema\Ref 用 `findByKey()` 查找。

贡献需要解析的新类依赖时，使用 `withSpecification` 在 Resolver 前声明。对已收集的依赖做结构转换的 augmenter 必须实现 `PipeInterface<Specification>` 并声明 Resolve 组，在 Refs 前插入；普通 callable 默认进入 Augment 组。组内按插入顺序执行，不能仅凭全局数组位置判断顺序。

## 验证

- `vendor/bin/pest tests/Unit/Swagger --compact`：原控制器/路由快照与 Spec 集成测试。
- `vendor/bin/phpstan analyse --memory-limit=4G --no-progress`：全仓类型检查。
- 真实 HTTP 集成位于 `e2e/webman-src/tests/Feature/SwaggerTest.php`；改对应源码后按根目录 E2E 流程同步、运行。

快照更新前比较解析后的文档语义，区分字段排序、Spec 默认推断与字段丢失。Pest 更新子集快照可能删除其他测试的快照，应检查并恢复无关删除。

用户用法见 README，日常使用建议见 `skills/webman-tech-swagger-best-practices/SKILL.md`，旧版升级方案见其 `references/migrate-attribute-to-spec.md`。
