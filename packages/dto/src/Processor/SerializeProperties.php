<?php

namespace WebmanTech\DTO\Processor;

use BackedEnum;
use DateTime;
use WebmanTech\DTO\BaseDTO;

/**
 * 读取属性并转换 DTO、日期和枚举值
 */
final class SerializeProperties implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        $data = [];
        foreach ($context->properties as $property) {
            $value = $context->readProperty($property);
            if (!$context->config->singleKey && is_array($value)) {
                $context->arrayProperties[] = $property;
            }
            $data[$property] = $context->config->singleKey
                ? $this->serializeSingleValue($value)
                : $this->serializeValue($value, $context);
        }
        $context->data = $data;
    }

    private function serializeSingleValue(mixed $value): mixed
    {
        return $value instanceof BaseDTO ? $value->toArray() : $value;
    }

    private function serializeValue(mixed $value, ToArrayContext $context): mixed
    {
        if (is_array($value)) {
            return array_map(
                static fn(mixed $item): mixed => $item instanceof BaseDTO ? $item->toArray() : $item,
                $value,
            );
        }
        if ($value instanceof BaseDTO) {
            return $value->toArray();
        }
        if ($value instanceof DateTime) {
            return $context->formatDateTime($value);
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
