# DTO 生成器使用指南

`packages/dto/web/index.html` 是一个离线 Web 工具，粘贴 JSON 数据即可生成 DTO 骨架。

## JSON 输入约定

```json
{
    "//title": "订单标题",
    "title": "order-001",
    "amount": 100,
    "?remark": null,
    "address": {
        "city": "Beijing",
        "street": "Chaoyang"
    },
    "items": [
        { "sku": "A001", "qty": 2 }
    ]
}
```

- `?key` → 可选字段，生成 `Type|null $key = null`
- `//key` → 该字段的描述，生成为 docblock 注释
- 对象值 → 自动生成嵌套类
- 数组值（元素为对象）→ 自动生成 `XxxItem` 类 + `@var XxxItem[]` docblock
- `null` 值 → 生成 `mixed|null $key = null`，需手动改为具体类型

## 类名决定基类

| 类名后缀 | 生成基类 |
|----------|----------|
| `XxxForm` | `BaseRequestDTO` |
| `XxxFormResult` | `BaseResponseDTO` |
| `XxxConfig` / `XxxConfigDTO` | `BaseConfigDTO` |
| 其他 | `BaseDTO` |

## 生成结果示例

输入类名 `App\DTO\CreateOrderForm`，生成：

```php
final class CreateOrderForm extends BaseRequestDTO
{
    /** @example order-001 */
    public string $title;

    /** @example 100 */
    public int $amount;

    public mixed|null $remark = null;

    public CreateOrderFormAddress $address;

    /** @var CreateOrderFormItemsItem[] */
    public array $items;

    public function handle(): CreateOrderFormResult
    {
    }
}

final class CreateOrderFormAddress extends BaseDTO
{
    public string $city;
    public string $street;
}

final class CreateOrderFormItemsItem extends BaseDTO
{
    public string $sku;
    public int $qty;
}
```

## 生成后需要手动补充

1. `null` 值字段生成为 `mixed|null`，改为具体类型（如 `string|null`）
2. 需要额外约束的字段加 `#[ValidationRules(...)]`
3. `handle()` 方法填入业务逻辑
4. 嵌套类如需跨 DTO 复用，提取到独立文件
