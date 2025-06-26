<?php

namespace WebmanTech\DTO;

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
                $this->getPublicProperties(),
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

    private static array $reflectionCache = [];

    /**
     * 获取当前类的所有 public 属性
     * @return string[]
     */
    private function getPublicProperties(?\ReflectionClass $reflectionClass = null): array
    {
        $reflectionClass ??= new \ReflectionClass(static::class);
        $className = $reflectionClass->getName();

        if (!isset(self::$reflectionCache[$className])) {
            $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);
            $propertyNames = array_map(function (\ReflectionProperty $property) {
                return $property->getName();
            }, $properties);

            if ($reflectionParentClass = $reflectionClass->getParentClass()) {
                $propertyNames = array_merge($this->getPublicProperties($reflectionParentClass), $propertyNames);
            }

            self::$reflectionCache[$className] = $propertyNames;
        }

        return self::$reflectionCache[$className];
    }
}
