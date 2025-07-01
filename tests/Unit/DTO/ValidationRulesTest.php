<?php

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum as RuleEnum;
use Illuminate\Validation\Rules\In as RuleIn;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

test('validation rules', function () {
    class DTOFromValidationRulesTestItem
    {
        public string $name;
        public ?int $age = 18;
    }

    class DTOFromValidationRulesTestParent
    {
        public string $parent;
        public int $parentId;
    }

    class DTOFromValidationRulesTest extends DTOFromValidationRulesTestParent
    {
        // 自动提取 required 和基础类型
        public string $string;
        public int $int;
        public float $float;
        public bool $bool;
        public mixed $mixed;
        public Closure $closure;
        public array $array;
        public DTOFromValidationRulesTestItem $child;
        // 复合类型
        public string|int $stringOrInt;
        public string|int|null $stringOrIntOrNull;
        // 自动提取 nullable
        public ?string $stringNullable;
        // 有默认值的
        public ?string $stringNullableNull = null;
        public int $intWithDefault = 1;
        // array 类型，每个子类是对象
        #[ValidationRules(arrayItem: DTOFromValidationRulesTestItem::class)]
        public array $children;
        // 不定义类型
        public $noTypeDefine;
        public $noTypeDefineWithDefault = 'abc';
        // 使用 ValidationRules
        #[ValidationRules(required: true)]
        public $validationRulesNoTypeDefineRequired;
        #[ValidationRules(string: true)]
        public $validationRulesNoTypeDefineForString;
        #[ValidationRules(boolean: true)]
        public $validationRulesNoTypeDefineForBoolean;
        #[ValidationRules(integer: true)]
        public $validationRulesNoTypeDefineForInteger;
        #[ValidationRules(numeric: true)]
        public $validationRulesNoTypeDefineForNumeric;
        #[ValidationRules(array: true)]
        public $validationRulesNoTypeDefineForArray;
        #[ValidationRules(array: true, arrayItem: DTOFromValidationRulesTestItem::class)]
        public $validationRulesNoTypeDefineForArrayWithItem;
        #[ValidationRules(object: DTOFromValidationRulesTestItem::class)]
        public $validationRulesNoTypeDefineForObject;
        #[ValidationRules(min: 100, max: 1000)]
        public int $validationRulesIntMinMax;
        #[ValidationRules(minLength: 100, maxLength: 1000)]
        public $validationRulesStringMinMax;
        //#[ValidationRules(in: ['a', 'b', 'c'])]
        //public $validationRulesIn;
        #[ValidationRules(rules: 'required|string')]
        public $validationRulesRules;
    }

    $rules = ReflectionReaderFactory::fromClass(DTOFromValidationRulesTest::class)
        ->getPublicPropertiesValidationRules();

    expect($rules)->toMatchSnapshot();
});

test('validation rule enum', function () {
    enum DTOFromValidationRulesTestEnumInt: int
    {
        case A = 1;
        case B = 2;
        case C = 3;
    }

    enum DTOFromValidationRulesTestEnumString: string
    {
        case A = 'a';
        case B = 'b';
        case C = 'c';
    }

    class DTOFromValidationRulesEnumTest
    {
        public DTOFromValidationRulesTestEnumInt $enumInt;
        public DTOFromValidationRulesTestEnumString $enumString;
        #[ValidationRules(enum: DTOFromValidationRulesTestEnumInt::class)]
        public $validationRulesNoTypeDefineForEnum;
        #[ValidationRules(enumOnly: [DTOFromValidationRulesTestEnumInt::A])]
        public DTOFromValidationRulesTestEnumInt $enumWithOnly;
        #[ValidationRules(enumExcept: [DTOFromValidationRulesTestEnumInt::A])]
        public DTOFromValidationRulesTestEnumInt $enumWithExcept;
    }

    $rules = ReflectionReaderFactory::fromClass(DTOFromValidationRulesEnumTest::class)
        ->getPublicPropertiesValidationRules();

    $fnGetRuleEnum = function (array $rules): ?RuleEnum {
        return Arr::first($rules, fn($rule) => $rule instanceof RuleEnum);
    };

    $ruleEnum = $fnGetRuleEnum($rules['enumInt']);
    expect($ruleEnum)->not->toBeEmpty()
        ->and($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumInt::A))->toBeTrue()
        ->and($ruleEnum->passes('abc', 1))->toBeTrue();

    $ruleEnum = $fnGetRuleEnum($rules['enumString']);
    expect($ruleEnum)->not->toBeEmpty()
        ->and($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumString::A))->toBeTrue()
        ->and($ruleEnum->passes('abc', 'a'))->toBeTrue();

    $ruleEnum = $fnGetRuleEnum($rules['validationRulesNoTypeDefineForEnum']);
    expect($ruleEnum)->not->toBeEmpty();

    $ruleEnum = $fnGetRuleEnum($rules['enumWithOnly']);
    expect($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumInt::A))->toBeTrue()
        ->and($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumInt::B))->toBeFalse();

    $ruleEnum = $fnGetRuleEnum($rules['enumWithExcept']);
    expect($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumInt::A))->toBeFalse()
        ->and($ruleEnum->passes('abc', DTOFromValidationRulesTestEnumInt::B))->toBeTrue();
});

test('validation rule in', function () {
    class DTOFromValidationRulesInTest
    {
        #[ValidationRules(in: ['a', 'b', 'c'])]
        public $in;
        #[ValidationRules(rules: 'in:a,b,c')]
        public $inString = 'a';
    }

    $rules = ReflectionReaderFactory::fromClass(DTOFromValidationRulesInTest::class)
        ->getPublicPropertiesValidationRules();

    $fnGetRuleIn = function (array $rules): ?RuleIn {
        return Arr::first($rules, fn($rule) => $rule instanceof RuleIn);
    };

    $ruleIn = $fnGetRuleIn($rules['in']);
    expect($ruleIn)->not->toBeEmpty()
        ->and($ruleIn->__toString())->toBe('in:"a","b","c"');

    $ruleIn = $rules['inString'][0];
    expect($ruleIn)->toBe('in:a,b,c');
});
