---
name: webman-tech-swagger-best-practices
description: OpenAPI 文档生成。触发：DTO 注解、@handle 模式、Schema 定义、多文档、安全配置、UI 定制、升级/迁移 Spec 模式（OpenApi\Attributes）。
---

# webman-tech/swagger 最佳实践

> **当前版本使用 Spec 模式**（`OpenApi\Spec` + swagger-php 7.x）。若项目仍是旧的 `OpenApi\Attributes` / `modify` 配置，先按 [references/migrate-attribute-to-spec.md](references/migrate-attribute-to-spec.md) 升级；以下写法只适用于 Spec 模式。

## 核心原则

使用 `OpenApi\Spec`，扩展通过 `builder_modify`；本版本直接迁移纯 Spec。属性约束放在 `OA\Property(schema: new OA\Schema(...))`，组件名称使用 `component:`。

1. **DTO 即文档**：用 DTO 类定义请求/响应结构，swagger 自动从中提取类型和验证规则
2. **`@handle` 一行搞定请求+响应**：用 `FormClass@handle` 同时声明请求 schema 和响应 schema
3. **Tag 放在 Controller 类上**，不要每个方法都写

DTO 的写法详见 `webman-tech-dto-best-practices` skill。

---

## 快速开始

### 推荐：集中管理注册逻辑

将 swagger 注册逻辑封装到一个专用类，而不是分散在配置文件里：

```php
// app/components/SwaggerRegister.php
use OpenApi\Spec as OA;
use OpenApi\Builder;
use OpenApi\Specification;
use Symfony\Component\Finder\Finder;
use WebmanTech\Swagger\Swagger;

final class SwaggerRegister
{
    public static function register(): void
    {
        Swagger::create()->registerRoute([
            'register_route' => true,
            'swagger_ui' => [
                'tag_sort' => ['认证', '用户', '订单'],  // 控制 Tag 在 UI 中的排序
            ],
            'basic_auth' => [
                'enable' => false,     
            ],
            'openapi_doc' => [
                'scan_path' => fn() => [
                    Finder::create()->files()->name('*.php')
                        ->in(app_path('api/controller'))
                        ->exclude(['example']),  // 排除示例目录
                ],
                'builder_modify' => function (Builder $builder): void {
                    $builder->withSpecification(function (Specification $spec): void {
                        $spec->info = new OA\Info(title: config('app.name') . ' API', version: '1.0.0');
                        $spec->add(new OA\Server(url: route_url('/api'), description: request()->host()));
                        $spec->add(new OA\Security\Scheme(
                            component: 'api_key', type: 'apiKey', name: 'X-Api-Key', in: 'header',
                        ));
                        $spec->openapi->security = [new OA\Security\Requirement(scheme: 'api_key')];
                    });
                },
                'host_forbidden' => [
                    'ip_white_list_intranet' => true,
                ],
            ],
        ]);
    }
}
```

```php
// config/route.php 或启动文件
SwaggerRegister::register();
```

使用 `builder_modify` 配置官方 Spec 扩展点：通过 `withSpecification` 读取运行时配置并贡献原生对象，批量调整 Schema 或 Operation 时通过 `withAugmenters` 注册 augmenter。

---

## 标准接口写法

### 推荐：`@handle` 模式（一行同时声明请求和响应）

```php
use OpenApi\Spec as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

#[OA\Tag(name: 'users', description: '用户管理')]  // Tag 放类上，不放方法上
final class UserController
{
    #[OA\Operation\Post(
        path: '/users',
        summary: '创建用户',
        x: [SchemaConstants::X_SCHEMA_REQUEST => UserCreateForm::class . '@handle']
        //                                                                  ^^^^^^^ 自动从 handle() 返回类型推断响应 schema
    )]
    public function create()
    {
        return UserCreateForm::fromRequest()->handle()->toResponse();
    }
}
```

`@handle` 的工作原理：swagger 读取 `handle()` 方法的返回类型，自动设置为 `x-schema-response`，无需手动声明。没有声明返回类型时只跳过响应推导（保留默认 200 响应），不会中断文档生成。

### DTO 类：让类型声明做文档

```php
use OpenApi\Spec as OA;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Attributes\ValidationRules;

// 请求 DTO — 不需要 #[OA\Schema]，BaseDTO 子类自动被识别
final class UserCreateForm extends BaseRequestDTO
{
    /**
     * 用户姓名
     * @example 张三
     */
    public string $name;

    /**
     * 年龄
     * @example 18
     */
    #[ValidationRules(min: 1, max: 120)]
    public int $age;

    /**
     * @var UserCreateFormAddressItem[]
     */
    public array|null $address = null;

    public function handle(): UserCreateFormResult
    {
        // 业务逻辑
        return new UserCreateFormResult(id: 1);
    }
}

// 响应 DTO — 用构造函数属性提升
final class UserCreateFormResult extends BaseResponseDTO
{
    public function __construct(
        /** @example 1 */
        public readonly int $id,
    ) {}
}

// 嵌套 DTO
final class UserCreateFormAddressItem extends BaseDTO
{
    /** @example 北京 */
    public string $city;
    /** @example 朝阳区 */
    public string $street;
}
```

---

## 文档注释规范

### `@example` 提供示例值

```php
/**
 * 用户状态
 * @example active
 */
public string $status;

/**
 * 年龄
 * @example 18
 */
public int $age;

/**
 * 标签列表
 * @var string[]
 * @example ["tag1", "tag2"]
 */
public array $tags = [];
```

### 属性描述写在 docblock 第一行

```php
/**
 * 订单编号（不是 @param，直接写描述）
 * @example ORD20240101001
 */
public string $orderNo;
```

---

## 多个请求 Schema

当一个接口需要合并多个 schema 时：

```php
#[OA\Operation\Post(
    path: '/orders',
    x: [
        SchemaConstants::X_SCHEMA_REQUEST => [
            CreateOrderForm::class,
            CommonPaginationForm::class,  // 合并分页参数
        ]
    ]
)]
```

---

## 响应包装结构（response_layout）

如果所有接口都有统一的响应结构（如 `{code, message, data}`），配置 `response_layout_class`：

```php
// 定义响应结构
#[OA\Schema]
final class ApiResponse
{
    /** @example 0 */
    #[OA\Property]
    public int $code = 0;
    /** @example success */
    #[OA\Property]
    public string $message = 'success';
    // data 字段由 response_layout_data_code 指定，自动填充
}

// 配置
'openapi_doc' => [
    'response_layout_class' => ApiResponse::class,
    'response_layout_data_code' => 'data',  // 默认就是 'data'
],
```

配置后，所有接口的 200 响应自动包裹在 `ApiResponse` 结构中，`data` 字段填充实际响应 schema。

单个接口可以用 `x-response-layout: null` 跳过包装：

```php
#[OA\Operation\Get(
    path: '/health',
    x: [SchemaConstants::X_RESPONSE_LAYOUT => null]  // 不包装
)]
```

---

## 多文档（多个 Swagger 实例）

```php
// 注册第二个文档
Swagger::create()->registerRoute([
    'route_prefix' => '/openapi/admin',
    'openapi_doc' => [
        'scan_path' => [app_path('admin')],
        'cache_key' => 'swagger:admin',  // 必须指定不同的 cache_key
    ],
]);
```

---

## 枚举类型

枚举自动提取描述，只需定义枚举类：

```php
enum StatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function description(): string  // 默认调用此方法
    {
        return match($this) {
            self::Active => '启用',
            self::Inactive => '禁用',
        };
    }
}
```

如果方法名不是 `description`，通过配置指定：

```php
'openapi_doc' => [
    'schema_enum_description_method' => 'label',  // 改为调用 label() 方法
],
```

---

## 路由注解（register_route: true 时）

开启 `register_route` 后，swagger 注解同时作为路由定义，无需在 route.php 中重复写：

```php
#[OA\Operation\Post(
    path: '/users',
    summary: '创建用户',
    x: [
        SchemaConstants::X_SCHEMA_REQUEST => UserCreateForm::class . '@handle',
        SchemaConstants::X_NAME => 'user.create',           // 命名路由
        SchemaConstants::X_MIDDLEWARE => [AuthMiddleware::class],  // 中间件
        // SchemaConstants::X_PATH => '/users/{id:\d+}',   // 路由 path 与 openapi path 不同时使用
    ]
)]
```

---

## 安全配置

生产环境需要保护 API 文档，详见 [references/security.md](references/security.md)。

---

## 常见错误

| 错误 | 原因 | 解决 |
|------|------|------|
| 只有示例路径 | 未发现可加载的 Spec Operation | 检查类的自动加载与 `scan_path`，使用 `OA\Operation\Get/Post` |
| Schema 找不到 | 类不能自动加载或普通类没有 Schema | 确保 PSR-4 可加载；DTO 自动发现，普通类加 Spec Schema/Property |
| `@handle` 不推断响应 | `handle()` 没有返回类型声明（或返回交集类型），只保留默认 200 响应 | 想自动出响应结构就给 `handle()` 加返回类型 `UserCreateFormResult` |
| 多实例文档互相覆盖 | 未指定不同 `cache_key` | 每个实例配置唯一的 `cache_key` |
| 枚举描述不显示 | 方法名不匹配 | 配置 `schema_enum_description_method` |
