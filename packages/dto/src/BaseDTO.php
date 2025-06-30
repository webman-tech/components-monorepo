<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

class BaseDTO
{
    /**
     * 需要额外包含的属性
     * @return string[]
     */
    protected function getToArrayIncludeProperties(): array
    {
        return [];
    }

    /**
     * 需要剔除的属性
     * @return string[]
     */
    protected function getToArrayExcludeProperties(): array
    {
        return [];
    }

    /**
     * 转为数组
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $properties = array_diff(
            array_merge(
                ReflectionReaderFactory::fromClass(static::class)->getPublicPropertiesName(),
                $this->getToArrayIncludeProperties(),
            ),
            $this->getToArrayExcludeProperties()
        );
        foreach ($properties as $property) {
            $value = $this->{$property};
            if (is_array($value)) {
                $value = array_map(function ($item) {
                    if ($item instanceof self) {
                        return $item->toArray();
                    }
                    return $item;
                }, $value);
            } elseif ($value instanceof self) {
                $value = $value->toArray();
            }
            $data[$property] = $value;
        }

        return $data;
    }
}
