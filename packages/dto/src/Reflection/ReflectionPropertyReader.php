<?php

namespace WebmanTech\DTO\Reflection;

use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use WebmanTech\DTO\Attributes\ValidationRules;

/**
 * @internal
 */
final class ReflectionPropertyReader
{
    public function __construct(
        private readonly ReflectionProperty $reflectionProperty
    )
    {
    }

    /**
     * 类型是否可以为 null
     */
    private function isTypeNullable(): bool
    {
        return $this->reflectionProperty->getType()?->allowsNull() ?? true;
    }

    /**
     * 值是否必填
     * @return bool
     */
    private function isValueRequired(): bool
    {
        return $this->reflectionProperty->getType() // 有类型定义
            && !$this->reflectionProperty->hasDefaultValue() // 没有设置默认值
            ;
    }

    private ?ValidationRules $validationRules = null;

    public function getValidationRules(): ValidationRules
    {
        if ($this->validationRules === null) {
            $reflectionAttributes = $this->reflectionProperty->getAttributes(ValidationRules::class, ReflectionAttribute::IS_INSTANCEOF);
            $validationRules = empty($reflectionAttributes)
                ? new ValidationRules()
                : $reflectionAttributes[0]->newInstance();

            // 检查必填
            if ($this->isValueRequired()) {
                $validationRules->required = true;
            }
            // 检查是否可以为 null
            if ($this->isTypeNullable()) {
                $validationRules->nullable = true;
            }
            // 获取类型
            if ($reflectionType = $this->reflectionProperty->getType()) {
                $reflectionNamedTypes = [];
                if ($reflectionType instanceof ReflectionUnionType) {
                    $reflectionNamedTypes = $reflectionType->getTypes();
                } elseif ($reflectionType instanceof ReflectionNamedType) {
                    $reflectionNamedTypes = [$reflectionType];
                }
                foreach ($reflectionNamedTypes as $reflectionNamedType) {
                    if (!$reflectionNamedType instanceof ReflectionNamedType) {
                        continue;
                    }
                    $typeName = $reflectionNamedType->getName();
                    if ($reflectionNamedType->isBuiltin()) {
                        match ($typeName) {
                            'int' => $validationRules->integer = true,
                            'string' => $validationRules->string = true,
                            'bool' => $validationRules->boolean = true,
                            'float' => $validationRules->numeric = true,
                            'array' => $validationRules->array = true,
                            default => null,
                        };
                    } else {
                        if (enum_exists($typeName)) {
                            $validationRules->enum = $typeName;
                        } elseif (class_exists($typeName)) {
                            $validationRules->object = $typeName;
                        }
                    }
                }
            }

            $this->validationRules = $validationRules;
        }
        return $this->validationRules;
    }
}
