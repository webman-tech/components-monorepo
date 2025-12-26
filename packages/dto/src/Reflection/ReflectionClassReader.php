<?php

namespace WebmanTech\DTO\Reflection;

use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use WeakMap;
use WebmanTech\DTO\Attributes\RequestPropertyIn;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\Attributes\ValidationRules;

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

    private ?array $propertyReflections = null;

    /**
     * 获取全部的属性的反射，包含父类的
     * @return array<string, ReflectionProperty>
     */
    public function getPropertyReflections(): array
    {
        if ($this->propertyReflections === null) {
            $data = [];
            $propertyReflections = $this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC); // 仅 public
            foreach ($propertyReflections as $propertyReflection) {
                if ($propertyReflection->isStatic()) {
                    // 不包含 static
                    continue;
                }
                $data[$propertyReflection->getName()] = $propertyReflection;
            }

            // 获取 parent 的
            if ($reflectionParentClass = $this->reflectionClass->getParentClass()) {
                $data = array_merge(
                    ReflectionReaderFactory::fromReflectionClass($reflectionParentClass)->getPropertyReflections(),
                    $data, // 子类的放在后面
                );
            }

            $this->propertyReflections = $data;
        }

        return $this->propertyReflections;
    }

    /**
     * 获取单个属性的反射
     */
    public function getPropertyReflection(string $propertyName): ?ReflectionProperty
    {
        return $this->getPropertyReflections()[$propertyName] ?? null;
    }

    private ?array $constructParameterReflections = null;

    /**
     * 获取全部的构造函数参数的反射
     * @return array<string, ReflectionParameter>
     */
    public function getConstructParameterReflections(): array
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

    /**
     * 获取单个构造函数参数的反射
     */
    public function getConstructParameterReflection(string $name): ?ReflectionParameter
    {
        return $this->getConstructParameterReflections()[$name] ?? null;
    }

    /**
     * 获取全部的属性 name
     * @return string[]
     */
    public function getPropertyNameList(): array
    {
        return array_keys($this->getPropertyReflections());
    }

    /**
     * 获取单个属性的 ValidationRules 注解实例
     * @return ($property is string ? ValidationRules|null : ValidationRules)
     */
    public function getAttributionValidationRules(string|ReflectionProperty|ReflectionParameter $property): ?ValidationRules
    {
        $reflection = is_string($property) ? $this->getPropertyReflection($property) : $property;
        if (!$reflection) {
            return null;
        }
        return $this->getFirstNamedAttributionInstance(
            $reflection,
            ValidationRules::class,
            default: fn() => new ValidationRules(),
            initializer: function (ValidationRules $attribution) use ($reflection): void {
                $attribution->fillWithReflection($reflection);
                $attribution->normalize();
            }
        );
    }

    /**
     * 获取全部的属性的 ValidationRules 注解实例
     * @return array<string, ValidationRules>
     */
    public function getAttributionValidationRulesList(): array
    {
        return array_map(fn(ReflectionProperty $reflection) => $this->getAttributionValidationRules($reflection), $this->getPropertyReflections());
    }

    /**
     * 获取单个属性的 RequestPropertyIn 注解实例
     */
    public function getAttributionRequestPropertyIn(string|ReflectionProperty|ReflectionParameter $property): ?RequestPropertyIn
    {
        $reflection = is_string($property) ? $this->getPropertyReflection($property) : $property;
        if (!$reflection) {
            return null;
        }
        return $this->getFirstNamedAttributionInstance(
            $reflection,
            RequestPropertyIn::class,
        );
    }

    /**
     * 获取全部的属性的 RequestPropertyIn 注解实例
     * @return array<string, RequestPropertyIn>
     */
    public function getAttributionRequestPropertyInList(): array
    {
        return array_filter(
            array_map(
                fn(ReflectionProperty $reflection) => $this->getAttributionRequestPropertyIn($reflection),
                $this->getPropertyReflections(),
            ),
        );
    }

    /**
     * 获取 ToArrayConfig 注解实例
     */
    public function getPropertiesToArrayConfig(): ?ToArrayConfig
    {
        return $this->getFirstNamedAttributionInstance(
            $this->reflectionClass,
            ToArrayConfig::class,
        );
    }

    /**
     * 获取全部属性的验证规则
     * @return array<string, array>
     */
    public function getPropertiesValidationRules(): array
    {
        $rules = [];
        foreach ($this->getAttributionValidationRulesList() as $key => $validationRules) {
            $itemRules = $validationRules->getRules($key);
            if (!$itemRules) {
                continue;
            }
            $rules = array_merge($rules, $itemRules);
        }

        return $rules;
    }

    /**
     * 根据 data 进行实例化
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
            // 根据 ValidationRule 中定义出来的类型进行赋值
            $validationRules = $this->getAttributionValidationRules($parameterReflection);
            $constructArgs[$key] = $validationRules->makeValueFromRawType($value);
            $shouldResolve = false;
            if ($validationRules->array && !$validationRules->arrayItem && is_array($value) && array_is_list($value) && $value) {
                // 对于列表数组的值，在 construct 上无法解析注释进行正确的类型赋值，此时需要在 property 中重新处理，所以不能移除掉
                $shouldResolve = true;
            }
            // 已经处理过的剔除掉
            if (!$shouldResolve) {
                unset($data[$key]);
            }
        }

        $objData = [];
        if ($data) {
            // 如果还有其他参数，给 public 属性赋值，以支持不在 construct 中的属性赋值
            foreach ($this->getPropertyReflections() as $key => $propertyReflection) {
                // 校验 $data 中存在
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                // 数据值
                $value = $data[$key];
                // 根据 ValidationRule 中定义出来的类型进行赋值
                $validationRules = $this->getAttributionValidationRules($propertyReflection);
                $value = $validationRules->makeValueFromRawType($value);
                if (array_key_exists($key, $constructArgs)) {
                    // 有些参数是构造参数（比如列表数组），放到构造参数中
                    // 能放构造参数的尽量放构造里，因为可能该参数被设为 readonly
                    $constructArgs[$key] = $value;
                } else {
                    $objData[$key] = $value;
                }
            }
        }

        $obj = $this->reflectionClass->newInstanceArgs($constructArgs);
        foreach ($objData as $key => $value) {
            $obj->{$key} = $value;
        }

        return $obj;
    }

    private ?WeakMap $attributionCache = null;

    /**
     * 获取单个属性的反射，取第一个，然后实例化
     * @template T of object
     * @param class-string<T> $attributionName
     * @return T|null
     */
    private function getFirstNamedAttributionInstance(
        ReflectionParameter|ReflectionProperty|ReflectionClass $reflection,
        string                                                 $attributionName,
        ?Closure                                               $default = null,
        ?Closure                                               $initializer = null,
    ): ?object
    {
        if ($this->attributionCache === null) {
            $this->attributionCache = new WeakMap();
        }
        if (!isset($this->attributionCache[$reflection])) {
            $this->attributionCache[$reflection] = [];
        }
        /** @var array $cache */
        $cache = $this->attributionCache[$reflection];

        if (!isset($cache[$attributionName])) {
            $reflectionAttributes = $reflection->getAttributes($attributionName, ReflectionAttribute::IS_INSTANCEOF);
            $value = empty($reflectionAttributes)
                ? ($default instanceof Closure ? $default() : null)
                : $reflectionAttributes[0]->newInstance();

            if ($initializer instanceof Closure) {
                $initializer($value);
            }

            $cache[$attributionName] = $value ?? '__NULL__';
            $this->attributionCache[$reflection] = $cache;
        }

        $value = $cache[$attributionName];
        return $value === '__NULL__' ? null : $value;
    }
}
