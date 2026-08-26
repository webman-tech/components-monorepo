# 完整控制器示例

展示 DTO 在控制器中的完整使用流程，包括请求验证、业务处理、响应返回。

## 请求 DTO

```php
final class CreateOrderRequest extends BaseRequestDTO
{
    public string $title;

    #[ValidationRules(min: 1, max: 9999)]
    public int $amount;

    /** @var OrderItemRequest[] */
    public array $items;

    #[RequestPropertyInHeader(name: 'X-Tenant-Id')]
    public string $tenantId;
}

final class OrderItemRequest extends BaseDTO
{
    public string $sku;
    public int $qty;
}
```

## 响应 DTO

```php
final class CreateOrderResponse extends BaseResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly ?string $remark = null,
    ) {}
}
```

## 控制器

```php
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

        $resp = new CreateOrderResponse(
            id: $order->id,
            status: $order->status,
        );
        return $resp->toResponse();
    }
}
```

## 关键点

1. **异常处理**：只 catch `DTOValidateException`（用户输入错误），不 catch `DTONewInstanceException`（代码 bug）
2. **响应构造**：BaseResponseDTO 推荐用构造函数属性提升
3. **Header 参数**：用 `#[RequestPropertyInHeader]` 标注非默认来源的字段
