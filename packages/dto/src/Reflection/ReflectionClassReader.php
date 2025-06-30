<?php

namespace WebmanTech\DTO\Reflection;

use ReflectionClass;
use ReflectionProperty;

/**
 * @internal
 */
final readonly class ReflectionClassReader
{
    public function __construct(
        private ReflectionClass $reflectionClass
    )
    {
    }

    /**
     * @return array<string, ReflectionPropertyReader>
     */
    public function getPublicProperties(): array
    {
        $data = [];
        $reflectionProperties = $this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($reflectionProperties as $reflectionProperty) {
            $data[$reflectionProperty->getName()] = new ReflectionPropertyReader($reflectionProperty);
        }

        // 获取 parent 的
        if ($reflectionParentClass = $this->reflectionClass->getParentClass()) {
            $data = array_merge(
                ReflectionReaderFactory::fromReflectionClass($reflectionParentClass)->getPublicProperties(),
                $data, // 子类的放在后面
            );
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function getPublicPropertiesName(): array
    {
        return array_keys($this->getPublicProperties());
    }

    /**
     * @return array<string, array>
     */
    public function getPublicPropertiesValidationRules(): array
    {
        $rules = [];
        foreach ($this->getPublicProperties() as $propertyName => $propertyReader) {
            $itemRules = $propertyReader->getValidationRules()->getRules($propertyName);
            if (!$itemRules) {
                continue;
            }

            $rules = array_merge($rules, $itemRules);
        }

        return $rules;
    }
}
