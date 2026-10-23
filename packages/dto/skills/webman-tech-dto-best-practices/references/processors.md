# 自定义处理器扩展

`configureFromDataPipeline()` 和 `configureToArrayPipeline()` 用于按 DTO 调整处理器。优先在共享 DTO 基类中配置，让同一类接口复用相同规则。

## 两个处理方向

| 处理方向 | 实现接口 | 上下文 | 扩展方法 |
|----------|----------|--------|----------|
| `fromData()` | `ProcessorInterface` | `FromDataContext` | `configureFromDataPipeline()` |
| `toArray()` | `ToArrayProcessorInterface` | `ToArrayContext` | `configureToArrayPipeline()` |

处理器实现 `__invoke()` 并直接修改上下文数据：

```php
final class NormalizeCode implements ProcessorInterface
{
    public function __invoke(FromDataContext $context): void
    {
        if (isset($context->data['code']) && is_string($context->data['code'])) {
            $context->data['code'] = strtoupper($context->data['code']);
        }
    }
}

final class SignResponse implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        $context->data = [
            'data' => $context->data,
            'signature' => Signer::sign($context->data),
        ];
    }
}
```

`FromDataContext::$data` 始终是待实例化的数组。`ToArrayContext::$data` 在处理过程中可能由数组变为 `singleKey` 对应的标量，自定义处理器应按插入位置处理相应类型。

## 自定义参数

处理器需要 DTO 级参数时，分别通过 `FromDataConfig::$custom` 和 `ToArrayConfig::$custom` 传递，避免为业务参数新增处理器构造函数或全局配置：

```php
#[FromDataConfig(custom: ['code_case' => 'upper'])]
final class CreateOrderForm extends BaseRequestDTO {}

#[ToArrayConfig(custom: ['envelope' => 'data'])]
final class CreateOrderFormResult extends BaseResponseDTO {}
```

```php
// ProcessorInterface
$codeCase = $context->config?->custom['code_case'] ?? null;

// ToArrayProcessorInterface
$envelope = $context->config->custom['envelope'] ?? null;
```

`FromDataConfig` 也可通过 `app.php` 的 `dto.from_data_config.request.custom` 或 `dto.from_data_config.base.custom` 设置默认值。类上的 `#[FromDataConfig]` 会整体覆盖对应的全局配置，不会合并 `custom`。调用 `toArray(new ToArrayConfig(custom: [...]))` 时，传入配置会整体覆盖类上的 `#[ToArrayConfig]`。

## 默认处理器顺序

`fromData()`：

1. `DecryptFields`
2. `SanitizeData`
3. `ValidateData`

`toArray()`：

1. `SelectProperties`
2. `SerializeProperties`
3. `FilterNullValues`
4. `ConvertEmptyArraysToObjects`
5. `EncryptFields`
6. `ExtractSingleKey`

用 `add()` 追加到末尾，用 `insert($processor, before: Xxx::class)` 插到指定处理器之前，用 `remove(Xxx::class)` 移除处理器。目标处理器不存在时，`insert()` 会抛出 `InvalidArgumentException`。

```php
abstract class ApiRequestDTO extends BaseRequestDTO
{
    protected static function configureFromDataPipeline(FromDataPipeline $processors): void
    {
        $processors->insert(new NormalizeCode(), before: ValidateData::class);
    }
}

abstract class ApiResponseDTO extends BaseResponseDTO
{
    protected static function configureToArrayPipeline(ToArrayPipeline $processors): void
    {
        $processors->add(new SignResponse());
    }
}
```

## 整体报文加解密示例

组件没有内置整体报文加解密。下面的处理器以及 `RequestCipher`、`ResponseCipher` 都是业务方实现，用于展示如何扩展 Processor。

```php
final class DecryptRequestPayload implements ProcessorInterface
{
    public function __invoke(FromDataContext $context): void
    {
        $context->data = RequestCipher::decrypt($context->data);
    }
}

final class EncryptResponsePayload implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        $context->data = ['data' => ResponseCipher::encrypt($context->data)];
    }
}
```

整体请求解密必须先于字段解密，整体响应加密通常在默认序列化完成后执行：

```php
abstract class EncryptedRequestDTO extends BaseRequestDTO
{
    protected static function configureFromDataPipeline(FromDataPipeline $processors): void
    {
        $processors->insert(
            new DecryptRequestPayload(),
            before: DecryptFields::class,
        );
    }
}

abstract class EncryptedResponseDTO extends BaseResponseDTO
{
    protected static function configureToArrayPipeline(ToArrayPipeline $processors): void
    {
        $processors->add(new EncryptResponsePayload());
    }
}
```

入参处理器接收到的是 `fromRequest()` 已提取出的数组。协议直接使用非 JSON 原始请求体时，先在共享请求 DTO 基类中重写 `getDataFromRequest()`，将原始内容提取为数组。

## 完整替换处理器

只有明确接管完整处理过程时，才在 `config/plugin/webman-tech/dto/app.php` 中完整替换默认处理器：

```php
return [
    'enable' => true,
    'dto' => [
        'from_data_processors' => [
            CustomFromDataProcessor::class,
        ],
        'to_array_processors' => [
            CustomToArrayProcessor::class,
        ],
    ],
];
```

配置中的类分别必须实现 `ProcessorInterface` 和 `ToArrayProcessorInterface`，并能无参实例化。完整替换后，字段选择、序列化、清洗和验证等默认能力不会自动保留。
