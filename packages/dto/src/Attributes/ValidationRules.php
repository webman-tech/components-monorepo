<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use BackedEnum;
use DateTime;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as RuleEnum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadFile;
use UnitEnum;
use Webman\Http\UploadFile as WebmanUploadFile;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Helper\DocBlockHelper;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class ValidationRules
{
    public function __construct(
        public null|string|array           $rules = null,
        public null|true                   $required = null,
        public null|true                   $nullable = null,
        public null|true                   $string = null,
        public null|true                   $boolean = null,
        public null|true                   $integer = null,
        public null|true                   $numeric = null,
        /**
         * @var null|class-string<UnitEnum>
         */
        public null|string                 $enum = null,
        public null|array                  $enumOnly = null,
        public null|array                  $enumExcept = null,
        public null|true                   $array = null,
        /**
         * @var null|class-string|ValidationRules
         */
        public null|string|ValidationRules $arrayItem = null,
        /**
         * @var null|class-string|true
         */
        public null|string|true            $object = null,
        public null|int|float              $min = null,
        public null|int|float              $max = null,
        public null|int                    $minLength = null,
        public null|int                    $maxLength = null,
        public null|array                  $in = null,
    )
    {
    }

    /**
     * 通过 Reflection 信息，填充 当前实例
     */
    public function fillWithReflection(ReflectionParameter|ReflectionProperty $reflection): void
    {
        // 检查必填
        $isValueRequired = false;
        if ($reflection instanceof ReflectionProperty) {
            $isValueRequired = $reflection->hasType() // 有类型定义
                && !$reflection->hasDefaultValue() // 没有设置默认值
            ;
            if (
                $isValueRequired
                && ReflectionReaderFactory::fromReflectionClass($reflection->getDeclaringClass())
                    ->getConstructParameterReflection($reflection->getName())
                    ?->isDefaultValueAvailable()
            ) {
                // 如果属性是必填的，但是已经在 construct 上赋值过了，则不再是必填的了
                $isValueRequired = false;
            }
        } elseif ($reflection instanceof ReflectionParameter) {
            $isValueRequired = !$reflection->isOptional() // 非可选的
                && !$reflection->isDefaultValueAvailable() // 没有设置默认值
            ;
        }
        if ($isValueRequired) {
            $this->required = true;
        }
        // 检查是否可以为 null
        $isTypeNullable = $reflection->getType()?->allowsNull() ?? true;
        if ($isTypeNullable) {
            $this->nullable = true;
        }
        // 获取类型
        if ($reflectionType = $reflection->getType()) {
            if ($reflectionType instanceof ReflectionNamedType) {
                // 仅支持 ReflectionNamedType，不支持复合类型（因为 laravel 中目前没有复合类型的校验器，需要自己写 callback）
                $typeName = $reflectionType->getName();
                if ($reflectionType->isBuiltin()) {
                    match ($typeName) {
                        'int' => $this->integer = true,
                        'string' => $this->string = true,
                        'bool' => $this->boolean = true,
                        'float' => $this->numeric = true,
                        'array' => $this->array = true,
                        default => null,
                    };
                } else {
                    if (enum_exists($typeName)) {
                        $this->enum = $typeName;
                    } elseif (class_exists($typeName)) {
                        $this->object = $typeName;
                    }
                }
            }
        }
        // 对 array 的 arrayItem 进行提取
        if ($this->array && !$this->arrayItem) {
            if ($reflection instanceof ReflectionProperty) {
                $arrayItemType = DocBlockHelper::extractClassPropertyArrayItemType($reflection);
                if ($arrayItemType instanceof ValidationRules && $arrayItemType->object === true) {
                    // 当解析时个对象时，当前实例应该是个对象，而不是 array
                    $this->array = null;
                    $this->object = true;
                    $this->arrayItem = $arrayItemType->arrayItem;
                } else {
                    $this->arrayItem = $arrayItemType;
                }
            }
        }
    }

    private bool $normalized = false;

    /**
     * 格式化规则
     */
    public function normalize(): void
    {
        if ($this->normalized) {
            return;
        }
        $this->normalized = true;

        if ($this->enum) {
            if (!enum_exists($this->enum)) {
                throw new \InvalidArgumentException('enum is not a enum class');
            }
            if (!is_a($this->enum, BackedEnum::class, true)) {
                // 纯粹的 Enum 因为不能从 data 赋值过来，因此不支持
                throw new \InvalidArgumentException('enum is not a BackedEnum class');
            }
        }
        if ($this->object) {
            if ($this->object === \Closure::class) {
                // 不能当 object 处理
                $this->object = null;
            } elseif (!is_bool($this->object) && !class_exists($this->object)) {
                throw new \InvalidArgumentException('object type error');
            }
        }
        if ($this->arrayItem) {
            if (!(
                is_string($this->arrayItem) && class_exists($this->arrayItem)
                || $this->arrayItem instanceof ValidationRules
            )) {
                throw new \InvalidArgumentException('arrayItem must be class-string or ValidationRules instance');
            }
            $this->array = true;
        }
        if ($this->minLength || $this->maxLength) {
            $this->string = true;
        }
        // 多种类型不允许同时存在，目前 laravel 不支持多类型验证
        $types = array_filter([
            $this->string,
            $this->boolean,
            $this->integer,
            $this->numeric,
            $this->array,
        ]);
        if (count($types) > 1) {
            throw new \InvalidArgumentException('only one type can be set');
        }
    }

    private ?array $parsedRules = null;

    /**
     * 获取最终构造出来的 rules
     * @return array<string, array>
     */
    public function getRules(string $key): array
    {
        $this->normalize();
        if ($this->parsedRules === null) {
            $this->parsedRules = $this->parseRules();
        }
        $rules = $this->parsedRules ? [$key => $this->parsedRules] : [];

        if ($this->object && $this->object !== true && class_exists($this->object)) {
            foreach (ReflectionReaderFactory::fromClass($this->object)->getPropertiesValidationRules() as $itemKey => $itemRules) {
                $rules[$key . '.' . $itemKey] = array_map(function ($rule) use ($key) {
                    if ($rule === 'required') {
                        return 'required_with:' . $key;
                    }
                    return $rule;
                }, $itemRules);
            }
        }
        if ($this->arrayItem) {
            if (is_string($this->arrayItem) && class_exists($this->arrayItem)) {
                foreach (ReflectionReaderFactory::fromClass($this->arrayItem)->getPropertiesValidationRules() as $itemKey => $itemRules) {
                    $rules[$key . '.*.' . $itemKey] = $itemRules;
                }
            } elseif ($this->arrayItem instanceof ValidationRules) {
                $rules[$key . '.*'] = $this->arrayItem->getRules('_PLACE_')['_PLACE_'];
            }
        }

        return $rules;
    }

    /**
     * 从原始类型的值，构造出 ValidationRule 中设定的类型的值
     */
    public function makeValueFromRawType(mixed $value): mixed
    {
        // null 支持
        if ($this->nullable && is_null($value)) {
            return null;
        }
        // 枚举
        if ($this->enum) {
            if (!is_string($value) && !is_int($value)) {
                throw new \InvalidArgumentException('cant make enum because value not string or int: ' . $this->enum);
            }
            /** @var class-string<BackedEnum> $enum normalize 中已经校验过 */
            $enum = $this->enum;
            return $enum::from($value);
        }
        // 对象
        if ($this->object && is_string($this->object)) {
            if (is_a($this->object, BaseDTO::class, true)) {
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('cant make object because value not array: ' . $this->object);
                }
                return $this->object::fromData($value);
            }
            if (is_a($this->object, DateTime::class, true)) {
                return ReflectionReaderFactory::fromClass($this->object)->newInstanceByData([
                    'datetime' => $value,
                    'time' => $value, // carbon 改变了参数名字，因此多传个 time
                ]);
            }
            if (
                is_a($this->object, WebmanUploadFile::class, true)
                || is_a($this->object, SymfonyUploadFile::class, true)
            ) {
                // 文件上传的类直接 返回赋值
                return $value;
            }
            throw new \InvalidArgumentException('cant make object because type not support: ' . $this->object);
        }
        // 数组
        if ($this->arrayItem) {
            $throwName = $this->arrayItem instanceof ValidationRules
                ? 'ValidationRules'
                : $this->arrayItem;
            if (!is_array($value)) {
                throw new \InvalidArgumentException('cant make arrayItem because value not array: ' . $throwName);
            }
            if (is_string($this->arrayItem) && class_exists($this->arrayItem)) {
                return array_map(fn($item) => ReflectionReaderFactory::fromClass($this->arrayItem)->newInstanceByData($item), $value);
            }
            if ($this->arrayItem instanceof ValidationRules) {
                return array_map(fn($item) => $this->arrayItem->makeValueFromRawType($item), $value);
            }
            throw new \InvalidArgumentException('arrayItem must be class-string or ValidationRules instance');
        }
        // 其他直接赋值
        return $value;
    }

    private function parseRules(): array
    {
        $this->normalize();

        $rulesAll = [];

        // 是否必填放最前面
        $rulesAll[] = $this->required === true ? 'required' : null;
        $rulesAll[] = $this->nullable === true ? 'nullable' : null;

        // 数据类型校验
        $rulesAll[] = ($this->string === true || $this->minLength || $this->maxLength) ? 'string' : null;
        $rulesAll[] = ($this->boolean === true) ? 'boolean' : null;
        $rulesAll[] = ($this->integer === true) ? 'integer' : null;
        $rulesAll[] = ($this->numeric === true) ? 'numeric' : null;
        $rulesAll[] = ($this->array === true) ? 'array' : null;
        if ($this->object && is_string($this->object)) {
            if (is_a($this->object, DateTime::class, true)) {
                $rulesAll[] = 'date';
            } elseif (is_a($this->object, BaseDTO::class, true)) {
                $rulesAll[] = 'array';
            }
        }

        // 数据范围检查
        $rulesAll[] = $this->min !== null ? ('min:' . $this->min) : null;
        $rulesAll[] = $this->max !== null ? ('max:' . $this->max) : null;
        $rulesAll[] = $this->minLength !== null ? ('min:' . $this->minLength) : null;
        $rulesAll[] = $this->maxLength !== null ? ('max:' . $this->maxLength) : null;
        if ($this->enum) {
            $rule = new RuleEnum($this->enum);
            if ($this->enumOnly) {
                $rule->only($this->enumOnly);
            }
            if ($this->enumExcept) {
                $rule->except($this->enumExcept);
            }
            $rulesAll[] = $rule;
        }
        if ($this->in) {
            $rulesAll[] = Rule::in($this->in);
        }

        // 自定义 rule
        $rules = $this->rules ?? [];
        if (is_string($rules)) {
            $rules = array_values(array_filter(explode('|', $rules)));
        }
        $rulesAll = array_merge($rulesAll, $rules);

        $data = [];
        foreach ($rulesAll as $item) {
            if ($item === null) {
                continue;
            }
            $uniqueKey = match (true) {
                is_string($item) => explode(':', $item)[0],
                is_object($item) => $item::class,
                default => throw new \InvalidArgumentException('ValidationRules::getRules() only support string or classObject'),
            };
            if (isset($rulesAll[$uniqueKey])) {
                continue;
            }
            $data[$uniqueKey] = $item;
        }
        return array_values($data);
    }
}
