<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Enums\RequestPropertyInEnum;
use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOValidateException;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

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
    public static function fromRequest(mixed $request = null, bool $validate = true): static
    {
        $data = static::getDataFromRequest($request);

        return static::fromData($data, validate: $validate);
    }

    /**
     * 从 request 获取数据
     * @param TypeRequest $request
     * @return array<string, mixed>
     */
    protected static function getDataFromRequest(mixed $request = null): array
    {
        $request = Integrations\Request::from($request);

        // 自动从 request 提取全部值
        $data = [];
        if ($requestPropertyIn = RequestPropertyInEnum::tryFromRequest($request)) {
            $data = $requestPropertyIn->getAllFromRequest($request);
        }

        // 从 RequestPropertyIn 的注解上提取特定值
        foreach (ReflectionReaderFactory::fromClass(static::class)->getAttributionRequestPropertyInList() as $propertyName => $requestPropertyIn) {
            $name = $requestPropertyIn->name ?: $propertyName;
            $data[$propertyName] = $requestPropertyIn->getInEnum()->getFromRequest($request, $name);
        }

        return $data;
    }
}
