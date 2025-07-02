<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use BackedEnum;
use Illuminate\Validation\Rule;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class ValidationRules
{
    public function __construct(
        public null|string|array $rules = null,
        public null|true         $required = null,
        public null|true         $nullable = null,
        public null|true         $string = null,
        public null|true         $boolean = null,
        public null|true         $integer = null,
        public null|true         $numeric = null,
        /**
         * @var class-string<BackedEnum>|null
         */
        public null|string       $enum = null,
        public null|array        $enumOnly = null,
        public null|array        $enumExcept = null,
        public null|true         $array = null,
        /**
         * @var class-string|null
         */
        public null|string       $arrayItem = null,
        /**
         * @var class-string|null
         */
        public null|string       $object = null,
        public null|int|float    $min = null,
        public null|int|float    $max = null,
        public null|int          $minLength = null,
        public null|int          $maxLength = null,
        public null|array        $in = null,
    )
    {
    }

    private bool $normalized = false;

    public function normalize(): self
    {
        if ($this->normalized) {
            return $this;
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
            } elseif (!class_exists($this->object)) {
                throw new \InvalidArgumentException('object is not a class');
            }
        }
        if ($this->arrayItem) {
            if (!class_exists($this->arrayItem)) {
                throw new \InvalidArgumentException('arrayItem is not a class');
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

        return $this;
    }

    private ?array $parsedRules = null;

    /**
     * @return array<string, array>
     */
    public function getRules(string $key): array
    {
        $this->normalize();
        if ($this->parsedRules === null) {
            $this->parsedRules = $this->parseRules();
        }
        $rules = $this->parsedRules ? [$key => $this->parsedRules] : [];

        if ($this->object) {
            foreach (ReflectionReaderFactory::fromClass($this->object)->getPublicPropertiesValidationRules() as $itemKey => $itemRules) {
                $rules[$key . '.' . $itemKey] = $itemRules;
            }
        }
        if ($this->arrayItem) {
            foreach (ReflectionReaderFactory::fromClass($this->arrayItem)->getPublicPropertiesValidationRules() as $itemKey => $itemRules) {
                $rules[$key . '.*.' . $itemKey] = $itemRules;
            }
        }

        return $rules;
    }

    private function parseRules(): array
    {
        $this->normalize();

        $rules1 = $this->rules ?? [];
        if (is_string($rules1)) {
            $rules1 = explode('|', $rules1);
        }

        $rules2 = array_filter([
            $this->required === true ? 'required' : null,
            $this->nullable === true ? 'nullable' : null,
            $this->string === true ? 'string' : null,
            $this->boolean === true ? 'boolean' : null,
            $this->integer === true ? 'integer' : null,
            $this->numeric === true ? 'numeric' : null,
            ($this->array === true || $this->object === true) ? 'array' : null,
            $this->min !== null ? 'min:' . $this->min : null,
            $this->max !== null ? 'max:' . $this->max : null,
        ]);

        $rules3 = [];
        if ($this->enum) {
            $rule = Rule::enum($this->enum);
            if ($this->enumOnly) {
                $rule->only($this->enumOnly);
            }
            if ($this->enumExcept) {
                $rule->except($this->enumExcept);
            }
            $rules3[] = $rule;
        }
        if ($this->in) {
            $rules3[] = Rule::in($this->in);
        }
        if ($this->minLength || $this->maxLength) {
            $rules3[] = 'string';
            if ($this->minLength) {
                $rules3[] = 'min:' . $this->minLength;
            }
            if ($this->maxLength) {
                $rules3[] = 'max:' . $this->maxLength;
            }
        }

        return collect($rules1)
            ->merge($rules2)
            ->merge($rules3)
            ->unique(function ($item) {
                if (is_string($item)) {
                    return explode(':', $item)[0];
                }
                if (is_object($item)) {
                    return $item::class;
                }
                throw new \InvalidArgumentException('ValidationRules::getRules() only support string or classObject');
            })
            ->values()
            ->toArray();
    }
}
