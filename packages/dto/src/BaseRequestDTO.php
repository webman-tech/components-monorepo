<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOValidateException;

/**
 * @phpstan-import-type TypeRequest from Integrations\Request
 */
class BaseRequestDTO extends BaseDTO
{
    /**
     * 从 request 创建
     * @param TypeRequest $request
     * @throws DTOValidateException|DTONewInstanceException
     */
    public static function fromRequest($request, ?string $defaultRequestType = null, bool $validate = true): static
    {
        $data = static::getDataFromRequest($request, $defaultRequestType);

        return static::fromData($data, validate: $validate);
    }

    /**
     * 通过配置从 request 获取指定的 key
     * @return array<string, string> key => where 或 key => where|whereKey
     */
    protected static function getConfigRequestKeyFrom(): array
    {
        return [];
    }

    /**
     * 从 request 获取数据
     * @param TypeRequest $request
     * @return array<string, mixed>
     */
    protected static function getDataFromRequest($request, ?string $defaultRequestType = null): array
    {
        $request = Integrations\Request::from($request);

        $data = match ($defaultRequestType) {
            'get' => $request->getAll(),
            'post' => $request->postAll(),
            'header' => $request->headerAll(),
            null => [],
            default => throw new \InvalidArgumentException('defaultRequestType error: ' . $defaultRequestType),
        };

        foreach (static::getConfigRequestKeyFrom() as $key => $from) {
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
}
