<?php

namespace WebmanTech\DTO\Reflection;

use DateTime;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use WeakMap;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;

/**
 * @internal
 */
final class ReflectionClassReader
{
    public function __construct(
        private readonly ReflectionClass $reflectionClass
    )
    {
    }

    /**
     * 获取全部的 public 属性 name
     * @return string[]
     */
    public function getPublicPropertiesName(): array
    {
        return array_keys($this->getPublicPropertyReflections());
    }

    /**
     * 获取全部的 public 属性的验证规则
     * @return array<string, array>
     */
    public function getPublicPropertiesValidationRules(): array
    {
        $rules = [];
        foreach ($this->getPublicPropertyReflections() as $key => $propertyReflection) {
            $itemRules = $this->getValidationRules($propertyReflection)->getRules($key);
            if (!$itemRules) {
                continue;
            }

            $rules = array_merge($rules, $itemRules);
        }

        return $rules;
    }

    /**
     * 获取某个 public 属性的验证规则
     * @param string $propertyName
     * @return ValidationRules|null
     */
    public function getPublicPropertyValidationRules(string $propertyName): ?ValidationRules
    {
        $propertyReflection = $this->getPublicPropertyReflections()[$propertyName] ?? null;
        if (!$propertyReflection) {
            return null;
        }

        return $this->getValidationRules($propertyReflection);
    }

    /**
     * 根据 data 进行实例化
     * @param array $data
     * @return mixed
     * @throws \ReflectionException
     */
    public function newInstanceByData(array $data): mixed
    {
        $constructArgs = [];
        foreach ($this->getConstructParameterReflections() as $key => $parameterReflection) {
            // 校验 $data 中是否有值
            if (!array_key_exists($key, $data)) {
                if ($parameterReflection->isOptional()) {
                    // 可选的，可以不填
                    $constructArgs[$key] = $parameterReflection->getDefaultValue();
                    continue;
                }
                throw new \InvalidArgumentException("class {$this->reflectionClass->getName()} construct parameter {$key} is missing");
            }
            // 数据值
            $value = $data[$key];
            unset($data[$key]); // 已经处理过的剔除掉
            // 根据 ValidationRule 中定义出来的类型进行赋值
            $validationRules = $this->getValidationRules($parameterReflection);
            $constructArgs[$key] = $this->makeValueByValidationRules($validationRules, $value);
        }
        $obj = $this->reflectionClass->newInstanceArgs($constructArgs);

        // 给 public 属性赋值，以支持不在 construct 中的属性赋值
        foreach ($this->getPublicPropertyReflections() as $key => $propertyReflection) {
            // 校验 $data 中存在
            if (!array_key_exists($key, $data)) {
                continue;
            }
            // 数据值
            $value = $data[$key];
            // 根据 ValidationRule 中定义出来的类型进行赋值
            $validationRules = $this->getValidationRules($propertyReflection);
            $obj->{$key} = $this->makeValueByValidationRules($validationRules, $value);
        }

        return $obj;
    }

    private ?array $publicPropertyReflections = null;

    /**
     * @return array<string, ReflectionProperty>
     */
    public function getPublicPropertyReflections(): array
    {
        if ($this->publicPropertyReflections === null) {
            $data = [];
            $propertyReflections = $this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
            foreach ($propertyReflections as $propertyReflection) {
                $data[$propertyReflection->getName()] = $propertyReflection;
            }

            // 获取 parent 的
            if ($reflectionParentClass = $this->reflectionClass->getParentClass()) {
                $data = array_merge(
                    ReflectionReaderFactory::fromReflectionClass($reflectionParentClass)->getPublicPropertyReflections(),
                    $data, // 子类的放在后面
                );
            }

            $this->publicPropertyReflections = $data;
        }

        return $this->publicPropertyReflections;
    }

    private ?array $constructParameterReflections = null;

    /**
     * @return array<string, ReflectionParameter>
     */
    private function getConstructParameterReflections(): array
    {
        if ($this->constructParameterReflections === null) {
            $data = [];
            if ($constructorReflection = $this->reflectionClass->getConstructor()) {
                $parameterReflections = $constructorReflection->getParameters();
                foreach ($parameterReflections as $parameterReflection) {
                    $data[$parameterReflection->getName()] = $parameterReflection;
                }
            }

            $this->constructParameterReflections = $data;
        }

        return $this->constructParameterReflections;
    }

    private function getConstructParameterReflectionByName(string $name): ?ReflectionParameter
    {
        return $this->getConstructParameterReflections()[$name] ?? null;
    }

    private ?WeakMap $validationRules = null;

    private function getValidationRules(ReflectionParameter|ReflectionProperty $reflection): ValidationRules
    {
        if ($this->validationRules === null) {
            $this->validationRules = new WeakMap();
        }
        if (!isset($this->validationRules[$reflection])) {
            $reflectionAttributes = $reflection->getAttributes(ValidationRules::class, ReflectionAttribute::IS_INSTANCEOF);
            $validationRules = empty($reflectionAttributes)
                ? new ValidationRules()
                : $reflectionAttributes[0]->newInstance();

            // 检查必填
            $isValueRequired = false;
            if ($reflection instanceof ReflectionProperty) {
                $isValueRequired = $reflection->hasType() // 有类型定义
                    && !$reflection->hasDefaultValue() // 没有设置默认值
                ;
                if ($isValueRequired && $this->getConstructParameterReflectionByName($reflection->getName())?->isDefaultValueAvailable()) {
                    // 如果属性是必填的，但是已经在 construct 上赋值过了，则不再是必填的了
                    $isValueRequired = false;
                }
            } elseif ($reflection instanceof ReflectionParameter) {
                $isValueRequired = !$reflection->isOptional() // 非可选的
                    && !$reflection->isDefaultValueAvailable() // 没有设置默认值
                ;
            }
            if ($isValueRequired) {
                $validationRules->required = true;
            }
            // 检查是否可以为 null
            $isTypeNullable = $reflection->getType()?->allowsNull() ?? true;
            if ($isTypeNullable) {
                $validationRules->nullable = true;
            }
            // 获取类型
            if ($reflectionType = $reflection->getType()) {
                if ($reflectionType instanceof ReflectionNamedType) {
                    // 仅支持 ReflectionNamedType，不支持复合类型（因为 laravel 中目前没有复合类型的校验器，需要自己写 callback）
                    $typeName = $reflectionType->getName();
                    if ($reflectionType->isBuiltin()) {
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

            $validationRules->normalize();
            $this->validationRules[$reflection] = $validationRules;
        }

        return $this->validationRules[$reflection];
    }

    private function makeValueByValidationRules(ValidationRules $validationRules, mixed $value): mixed
    {
        // 枚举
        if ($validationRules->enum) {
            if (!is_string($value) && !is_int($value)) {
                throw new \InvalidArgumentException('cant make enum because value not string or int: ' . $validationRules->enum);
            }
            return $validationRules->enum::from($value);
        }
        // 对象
        if ($validationRules->object) {
            if (is_a($validationRules->object, BaseDTO::class, true)) {
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('cant make object because value not array: ' . $validationRules->object);
                }
                return $validationRules->object::fromData($value);
            }
            if (is_a($validationRules->object, DateTime::class, true)) {
                return ReflectionReaderFactory::fromClass($validationRules->object)->newInstanceByData([
                    'datetime' => $value,
                    'time' => $value, // carbon 改变了参数名字，因此多传个 time
                ]);
            }
            throw new \InvalidArgumentException('cant make object because type not support: ' . $validationRules->object);
        }
        // 数组
        if ($validationRules->arrayItem) {
            $throwName = $validationRules->arrayItem instanceof ValidationRules
                ? 'ValidationRules'
                : $validationRules->arrayItem;
            if (!is_array($value)) {
                throw new \InvalidArgumentException('cant make arrayItem because value not array: ' . $throwName);
            }
            if (is_string($validationRules->arrayItem) && class_exists($validationRules->arrayItem)) {
                return array_map(fn($item) => ReflectionReaderFactory::fromClass($validationRules->arrayItem)->newInstanceByData($item), $value);
            }
            if ($validationRules->arrayItem instanceof ValidationRules) {
                return array_map(fn($item) => $this->makeValueByValidationRules($validationRules->arrayItem, $item), $value);
            }
            throw new \InvalidArgumentException('arrayItem must be class-string or ValidationRules instance');
        }
        // 其他直接赋值
        return $value;
    }
}
