---
name: webman-tech-dto-best-practices
description: webman-tech/dto 最佳实践。使用场景：用户使用 WebmanTech\DTO 相关类时，给出明确的推荐用法。
---

# webman-tech/dto 最佳实践

## 核心原则

1. **让 PHP 类型声明做验证**，不要重复写 `#[ValidationRules]`
2. **只标注例外**，默认行为已经够用
3. **验证失败是用户错误，类型错误是代码错误**，两者分开处理

---

## 选择基类

```
需要从 HTTP 请求取数据？  → BaseRequestDTO
需要返回 HTTP 响应？      → BaseResponseDTO
需要读取应用配置？        → BaseConfigDTO
只是嵌套数据结构？        → BaseDTO
```

---

## 用生成器快速起步

`packages/dto/web/index.html` 是一个离线 Web 工具，粘贴 JSON 数据即可生成 DTO 骨架，详见 [references/generator.md](references/generator.md)。

---

## 手写代码规范

以下规范与生成器保持一致，手写时应遵守。

### 所有 DTO 类都用 final

```php
// ✅
final class CreateOrderForm extends BaseRequestDTO {}

// ❌
class CreateOrderForm extends BaseRequestDTO {}
```

### 命名约定决定基类

| 类名后缀 | 基类 |
|----------|------|
| `XxxForm` | `BaseRequestDTO` |
| `XxxFormResult` | `BaseResponseDTO` |
| `XxxConfig` / `XxxConfigDTO` | `BaseConfigDTO` |
| 其他 | `BaseDTO` |

### BaseRequestDTO 要有 handle() 方法

```php
final class CreateOrderForm extends BaseRequestDTO
{
    public string $title;
    public int $amount;

    public function handle(): CreateOrderFormResult
    {
        // 业务逻辑
    }
}
```

### BaseResponseDTO 用构造函数属性提升

```php
// ✅ BaseResponseDTO 用构造函数
final class CreateOrderFormResult extends BaseResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly string|null $remark = null,
    ) {}
}

// ✅ BaseDTO / BaseRequestDTO 用 public 属性
final class CreateOrderForm extends BaseRequestDTO
{
    public string $title;
    public int $amount;
}
```

### 可选字段写 Type|null $field = null

```php
// ✅
public string|null $remark = null;

// ❌ 避免混用
public ?string $remark = null;
```

### 嵌套类命名：{Parent}{Key} 和 {Parent}{Key}Item

```php
final class CreateOrderForm extends BaseRequestDTO
{
    public CreateOrderFormAddress $address;       // 嵌套对象 → CreateOrderFormAddress

    /** @var CreateOrderFormItemsItem[] */
    public array $items;                          // 数组元素 → CreateOrderFormItemsItem
}

final class CreateOrderFormAddress extends BaseDTO { ... }
final class CreateOrderFormItemsItem extends BaseDTO { ... }
```

---

## BaseRequestDTO — 推荐写法

### 类型声明即验证规则，不要重复写

```php
// ✅ 正确：PHP 类型已经表达了验证意图
class CreateUserRequest extends BaseRequestDTO
{
    public string $name;          // required + string，自动推断
    public int $age;              // required + integer，自动推断
    public ?string $bio = null;   // nullable + 有默认值 = 非必填，自动推断
    public StatusEnum $status;    // required + enum 验证，自动推断
    public AddressDTO $address;   // required + 嵌套验证，自动推断
}

// ❌ 错误：重复声明，噪音
class CreateUserRequest extends BaseRequestDTO
{
    #[ValidationRules(required: true, string: true)]  // 多余，PHP 类型已经表达了
    public string $name;
}
```

### 只在需要额外约束时加注解

```php
class CreateUserRequest extends BaseRequestDTO
{
    #[ValidationRules(rules: 'email')]          // 追加 email 格式验证
    public string $email;

    #[ValidationRules(min: 1, max: 120)]        // 追加范围限制
    public int $age;

    #[ValidationRules(minLength: 2, maxLength: 50)]
    public string $name;
}
```

### 数组类型：用 docblock，不用注解

```php
// ✅ 推荐：docblock 更简洁
class OrderRequest extends BaseRequestDTO
{
    /** @var OrderItemRequest[] */
    public array $items;

    /** @var string[] */
    public array $tags;
}

// ❌ 不推荐：注解更啰嗦，效果相同
class OrderRequest extends BaseRequestDTO
{
    #[ValidationRules(arrayItem: OrderItemRequest::class)]
    public array $items;
}
```

### RequestPropertyIn：只标注例外来源

默认行为已覆盖 90% 场景（GET→query，POST json→json body，POST form→form body），
**只在字段来自非默认位置时才加注解**：

```php
// ✅ 正确：只标注例外
class SearchRequest extends BaseRequestDTO
{
    public string $keyword;       // 不标注，走默认逻辑

    #[RequestPropertyInHeader(name: 'X-Tenant-Id')]  // 来自 header，需要标注
    public string $tenantId;

    #[RequestPropertyInPath]      // 来自路由参数，需要标注
    public int $userId;
}

// ❌ 错误：标注了默认行为，多余
class SearchRequest extends BaseRequestDTO
{
    #[RequestPropertyInQuery]     // GET 请求本来就取 query，不需要标注
    public string $keyword;
}
```

### 跨字段验证：用 getExtraValidationRules

```php
class RegisterRequest extends BaseRequestDTO
{
    public string $password;
    public string $passwordConfirm;

    protected static function getExtraValidationRules(): array
    {
        return ['passwordConfirm' => 'same:password'];
    }
}
```

---

## 异常处理：两种异常含义不同

```php
try {
    $dto = CreateUserRequest::fromRequest();
} catch (DTOValidateException $e) {
    // 用户输入不合法，正常业务流程，必须处理
    return json(['errors' => $e->getErrors()], 422);
}
// DTONewInstanceException 不要 catch：
// 它表示数据通过了验证但无法赋值，是代码 bug，应该让它抛出暴露问题
```

---

## BaseResponseDTO — 推荐写法

### 保持结构一致性，不要默认 ignoreNull

```php
// ✅ 推荐：null 字段保留，客户端能预期字段结构
class UserResponse extends BaseResponseDTO
{
    public int $id;
    public string $name;
    public ?string $bio = null;  // 输出 "bio": null，结构稳定
}

// ❌ 不推荐作为默认：ignoreNull 会让响应结构不稳定
#[ToArrayConfig(ignoreNull: true)]
class UserResponse extends BaseResponseDTO { ... }
// 有时返回 {"id":1,"name":"Alice","bio":"..."} 有时返回 {"id":1,"name":"Alice"}
// 客户端需要额外判断字段是否存在
```

`ignoreNull: true` 只在明确需要精简输出时使用，例如稀疏数据、动态字段场景。

### 敏感字段用 exclude

```php
#[ToArrayConfig(exclude: ['password', 'token'])]
class UserResponse extends BaseResponseDTO
{
    public int $id;
    public string $name;
    public string $password;  // 不会出现在输出中
}
```

---

## BaseConfigDTO — 推荐写法

### 用构造函数定义默认值，getAppConfig 读配置文件

```php
class SwaggerConfig extends BaseConfigDTO
{
    public function __construct(
        public string $title   = 'API Docs',
        public bool   $enabled = true,
        public array  $servers = [],
    ) {}

    protected static function getAppConfig(): array
    {
        return config('plugin.swagger.app', []);
    }
}

$config = SwaggerConfig::fromConfig();                       // 读配置文件
$config = SwaggerConfig::fromConfig(['title' => 'My API']); // 运行时覆盖
```

### 注意：列表数组是追加合并，不是覆盖

```php
// 配置文件: ['servers' => ['https://prod.example.com']]
// 传参:     ['servers' => ['https://dev.example.com']]
// 结果:     ['servers' => ['https://prod.example.com', 'https://dev.example.com']]
// 如果不想追加，传参前先确保配置文件中该字段为空
```

---

## FromDataConfig — 全局配置优于逐类注解

不要在每个 DTO 上加 `#[FromDataConfig]`，在配置文件统一设置：

```php
// config/dto.php
return [
    'from_data_config' => [
        'request' => ['trim' => true],  // 所有 BaseRequestDTO 自动 trim
    ],
];
```

只有当某个 DTO 需要与全局配置不同的行为时，才在类上加注解覆盖。

---

## 嵌套类型

直接用 PHP 类型声明，框架自动递归验证和转换：

```php
class AddressDTO extends BaseDTO
{
    public string $city;
    public string $street;
}

class OrderRequest extends BaseRequestDTO
{
    public string $title;
    public AddressDTO $address;          // 必填嵌套对象，自动递归验证
    public ?AddressDTO $billing = null;  // 可选嵌套对象

    /** @var AddressDTO[] */
    public array $extraAddresses;        // 嵌套对象数组
}
```

传入数据：

```json
{
    "title": "order1",
    "address": { "city": "Beijing", "street": "Chaoyang" },
    "billing": null,
    "extraAddresses": [
        { "city": "Shanghai", "street": "Pudong" }
    ]
}
```

`$req->address` 直接是 `AddressDTO` 实例，`$req->extraAddresses` 是 `AddressDTO[]`，无需手动转换。

---

## 多态类型（进阶）

当一个字段的类型取决于另一个字段的值时使用：

```php
class ShipmentRequest extends BaseRequestDTO
{
    public string $type;  // 'normal' | 'express'

    #[ValidationRules(nullable: true, discriminator: [
        'property' => 'type',
        'mapping'  => [
            'normal'  => NormalShipmentDTO::class,
            'express' => ExpressShipmentDTO::class,
        ],
    ])]
    public NormalShipmentDTO|ExpressShipmentDTO|null $detail = null;
}
```

---

## 完整控制器示例

```php
// 请求
class CreateOrderRequest extends BaseRequestDTO
{
    public string $title;

    #[ValidationRules(min: 1, max: 9999)]
    public int $amount;

    /** @var OrderItemRequest[] */
    public array $items;

    #[RequestPropertyInHeader(name: 'X-Tenant-Id')]
    public string $tenantId;
}

class OrderItemRequest extends BaseDTO
{
    public string $sku;
    public int $qty;
}

// 响应
class CreateOrderResponse extends BaseResponseDTO
{
    public int $id;
    public string $status;
    public ?string $remark = null;
}

// 控制器
class OrderController
{
    public function create(): mixed
    {
        try {
            $req = CreateOrderRequest::fromRequest();
        } catch (DTOValidateException $e) {
            return json(['errors' => $e->getErrors()], 422);
        }

        $order = OrderService::create($req);

        $resp = new CreateOrderResponse();
        $resp->id     = $order->id;
        $resp->status = $order->status;
        return $resp->toResponse();
    }
}
```

---

## 常见错误

| 错误 | 原因 | 解决 |
|------|------|------|
| 枚举字段报错 | 使用了 `UnitEnum` | 改用 `BackedEnum`（`enum Foo: string`） |
| 嵌套 DTO 字段为空时报错 | 字段非 nullable | 改为 `?NestedDTO $field = null` |
| 数组元素类型不转换 | 缺少类型声明 | 加 docblock `@var Foo[]` |
| 验证通过但字段值错误 | 类型声明与传入数据不匹配 | 检查 PHP 类型声明是否与预期一致 |
| `DTONewInstanceException` | 代码 bug，不是用户错误 | 不要 catch，让它暴露 |
