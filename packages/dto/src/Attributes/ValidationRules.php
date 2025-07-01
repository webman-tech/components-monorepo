<?php

namespace WebmanTech\DTO\Attributes;

use Illuminate\Validation\Rule;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
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
        public null|string                 $enum = null,
        public null|array                  $enumOnly = null,
        public null|array                  $enumExcept = null,
        public null|true                   $array = null,
        public null|string|ValidationRules $arrayWithItem = null,
        public null|string                 $object = null,
        public null|int|float              $min = null,
        public null|int|float              $max = null,
        public null|int                    $minLength = null,
        public null|int                    $maxLength = null,
        public null|array                  $in = null,
    )
    {
    }

    private ?array $parsedRules = null;

    /**
     * @return array<string, array>
     */
    public function getRules(string $key): array
    {
        if ($this->parsedRules === null) {
            $this->parsedRules = $this->parseRules();
        }
        $rules = $this->parsedRules ? [$key => $this->parsedRules] : [];

        if ($this->object && class_exists($this->object)) {
            foreach (ReflectionReaderFactory::fromClass($this->object)->getPublicPropertiesValidationRules() as $itemKey => $itemRules) {
                $rules[$key . '.' . $itemKey] = $itemRules;
            }
        }
        if ($this->arrayWithItem && class_exists($this->arrayWithItem)) {
            foreach (ReflectionReaderFactory::fromClass($this->arrayWithItem)->getPublicPropertiesValidationRules() as $itemKey => $itemRules) {
                $rules[$key . '.*.' . $itemKey] = $itemRules;
            }
        }

        return $rules;
    }

    private function parseRules(): array
    {
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
            $this->array === true ? 'array' : null,
            $this->min !== null ? 'min:' . $this->min : null,
            $this->max !== null ? 'max:' . $this->max : null,
        ]);

        $rules3 = [];
        if ($this->enum && enum_exists($this->enum)) {
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
