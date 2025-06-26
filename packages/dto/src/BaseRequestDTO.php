<?php

namespace WebmanTech\DTO;

use Illuminate\Support\Arr;
use Webman\Http\Request;
use WebmanTech\DTO\Exceptions\DTOAssignPropertyException;
use WebmanTech\DTO\Exceptions\DTOValidateFailsException;

class BaseRequestDTO extends BaseDTO
{
    final public function __construct()
    {
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * 从 request 创建
     * @throws DTOValidateFailsException
     */
    public static function fromRequest(Request $request, ?string $defaultRequestType = null): static
    {
        $self = new static();

        $data = $self->getDataFromRequest($request, $defaultRequestType);
        $data = $self->validateData($data);
        $self->assignByArray($data);

        return $self;
    }

    /**
     * 通过配置从 request 获取指定的 key
     * @return array<string, string> key => where 或 key => where|whereKey
     */
    protected function getConfigRequestKeyFrom(): array
    {
        return [];
    }

    /**
     * 从 request 获取数据
     * @return array<string, mixed>
     */
    protected function getDataFromRequest(Request $request, ?string $defaultRequestType = null): array
    {
        $data = match ($defaultRequestType) {
            'get' => $request->get(),
            'post' => $request->post(),
            'header' => $request->header(),
            null => [],
            default => throw new \InvalidArgumentException('defaultRequestType error: ' . $defaultRequestType),
        };

        foreach ($this->getConfigRequestKeyFrom() as $key => $from) {
            $arr = explode('|', $from);
            [$where, $whereKey] = match (count($arr)) {
                1 => [$arr[0], $key],
                2 => [$arr[0], $arr[1]],
                default => throw new \InvalidArgumentException('getConfigRequestKeyFrom config error: ' . $from),
            };
            $value = match ($where) {
                'get' => $request->get($whereKey),
                'post' => $request->post($whereKey),
                'header' => $request->header($whereKey),
                'body' => $request->rawBody(),
                default => null,
            };
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * 验证规则
     * @return array<string, string|array|\Closure>
     */
    protected function getConfigValidateRules(): array
    {
        return [];
    }

    /**
     * 验证数据
     * @param array<string, mixed> $data
     * @return array<string, mixed> 验证过的数据
     * @throws DTOValidateFailsException
     */
    protected function validateData(array $data): array
    {
        $rules = $this->getConfigValidateRules();
        if (!$rules) {
            return $data;
        }
        $validator = validator($data, $rules);
        if ($validator->fails()) {
            throw new DTOValidateFailsException(
            // 只取每个 key 的第一次个错误
                array_map(
                    fn($messages) => is_array($messages) ? Arr::first($messages) : $messages,
                    $validator->errors()->toArray(),
                )
            );
        }

        return $data;
    }

    /**
     * 将 array 数据赋值给当前对象
     * @param array<string, mixed> $data
     * @throws DTOValidateFailsException
     */
    protected function assignByArray(array $data): void
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                try {
                    $this->$k = $v;
                } catch (\Throwable $e) {
                    // Cannot assign string to property Xx::$yy of type int
                    $message = $e->getMessage();
                    if (
                        str_contains($message, 'Cannot assign')
                        && str_contains($message, 'to property')
                        && str_contains($message, 'of type')
                    ) {
                        throw new DTOAssignPropertyException($k, $e);
                    }
                }
            }
        }
    }
}
