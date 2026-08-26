# 多态类型（Discriminator）

当一个字段的类型取决于另一个字段的值时使用 discriminator。

## 基本用法

```php
final class ShipmentRequest extends BaseRequestDTO
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

## 工作原理

1. 验证器读取 `type` 字段的值
2. 根据 mapping 确定 `$detail` 应该实例化为哪个 DTO 类
3. 自动递归验证选中的 DTO

## 适用场景

- API 接收不同类型的 payload（如支付方式的支付宝/微信）
- 事件系统中不同类型的事件数据
- 配置系统中不同模块的配置结构

## 注意事项

- discriminator 字段必须是 `BackedEnum` 或字符串
- mapping 中的类必须都是 BaseDTO 的子类
- 字段声明需要包含所有可能的类型（union type）
