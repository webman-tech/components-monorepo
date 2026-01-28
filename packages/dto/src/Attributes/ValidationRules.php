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
        /**
         * 是否使用浅层验证（不展开嵌套 DTO 的验证规则）
         * - true: 对于嵌套对象/数组，只验证基础类型，不递归获取子 DTO 的验证规则
         * - false: 完整验证，会展开所有嵌套规则（默认）
         */
        public bool                         $shallowValidation = false,
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
     * 修复嵌套对象中的 required_with 前缀
     * @param array $rules 规则数组
     * @param string $parentKey 父级 key（当前对象相对于其父级的路径）
     * @return array 修复后的规则数组
     */
    private function fixRequiredWithPrefix(array $rules, string $parentKey): array
    {
        return array_map(function ($rule) use ($parentKey) {
            if ($rule === 'required') {
                // 'required' 表示该字段依赖于其父对象的存在
                // 例如：level2.title 依赖于 level2
                return 'required_with:' . $parentKey;
            }

            if (is_string($rule) && str_starts_with($rule, 'required_with:')) {
                // 已有的 required_with 规则需要拼接父级路径
                // 例如：required_with:level3 -> required_with:level2.level3
                $originalValue = substr($rule, strlen('required_with:'));
                return 'required_with:' . $parentKey . '.' . $originalValue;
            }

            return $rule;
        }, $rules);
    }

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
            // 检查是否是 BaseDTO，如果是，获取完整的验证规则（包括额外规则）
            if (is_a($this->object, BaseDTO::class, true)) {
                // 浅层验证时，不展开嵌套 DTO 的验证规则
                if (!$this->shallowValidation) {
                    // 调用子 DTO 的 getValidationRules() 方法，获取所有规则（包括额外规则）
                    /** @var array $childRules */
                    $childRules = $this->object::getValidationRules();
                    foreach ($childRules as $itemKey => $itemRules) {
                        $rules[$key . '.' . $itemKey] = $this->fixRequiredWithPrefix($itemRules, $key);
                    }
                }
            } else {
                // 非 BaseDTO，只获取属性验证规则
                foreach (ReflectionReaderFactory::fromClass($this->object)->getPropertiesValidationRules() as $itemKey => $itemRules) {
                    $rules[$key . '.' . $itemKey] = $this->fixRequiredWithPrefix($itemRules, $key);
                }
            }
        }
        if ($this->arrayItem) {
            if (is_string($this->arrayItem) && class_exists($this->arrayItem)) {
                // 检查是否是 BaseDTO，如果是，获取完整的验证规则（包括额外规则）
                if (is_a($this->arrayItem, BaseDTO::class, true)) {
                    // 浅层验证时，不展开数组项 DTO 的验证规则
                    if (!$this->shallowValidation) {
                        /** @var array $childRules */
                        $childRules = $this->arrayItem::getValidationRules();
                        foreach ($childRules as $itemKey => $itemRules) {
                            $rules[$key . '.*.' . $itemKey] = $itemRules;
                        }
                    }
                } else {
                    foreach (ReflectionReaderFactory::fromClass($this->arrayItem)->getPropertiesValidationRules() as $itemKey => $itemRules) {
                        $rules[$key . '.*.' . $itemKey] = $itemRules;
                    }
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
        if ($this->nullable && ($value === null || $value === '')) {
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
                // 空数组且字段可空时，返回 null（避免创建空 DTO 对象）
                if ($value === [] && $this->nullable) {
                    return null;
                }
                return $this->object::fromData($value, validate: false);
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
            if (isset($data[$uniqueKey])) {
                continue;
            }
            $data[$uniqueKey] = $item;
        }

        $result = array_values($data);

        // 检查是否有 bail 规则，如果有则提取到最前面
        if (in_array('bail', $result, true)) {
            $bailIndex = array_search('bail', $result, true);
            if ($bailIndex > 0) {
                // 将 bail 移到最前面
                array_splice($result, $bailIndex, 1);
                array_unshift($result, 'bail');
            }
        }

        return $result;
    }
}
