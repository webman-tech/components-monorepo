<?php

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum as RuleEnum;
use Illuminate\Validation\Rules\In as RuleIn;
use Webman\Http\UploadFile;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

test('validation rules', function () {
    class DTOFromValidationRulesTestItem extends BaseDTO
    {
        public string $name;
        public ?int $age = 18;
    }

    class DTOFromValidationRulesTestParent extends BaseDTO
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
        public DateTime $dateTime;
        public UploadFile $file;
        public DTOFromValidationRulesTestItem $child;
        public ?DTOFromValidationRulesTestItem $childNullable = null;
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
        /**
         * @var array|DTOFromValidationRulesTestItem[]
         */
        public array $childrenUseDoc;
        // array 类型，每个子类是 ValidationRules
        #[ValidationRules(arrayItem: new ValidationRules(string: true))]
        public array $childrenNames;
        /**
         * @var string[]
         */
        public array $childrenNamesUseDoc;
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
        #[ValidationRules(object: true, arrayItem: new ValidationRules(string: true))]
        public array $validationRulesWithTypeArrayButObject;
        /**
         * @var array<string, string>
         */
        public array $validationRulesWithTypeArrayButObjectUseDoc;
        #[ValidationRules(min: 100, max: 1000)]
        public int $validationRulesIntMinMax;
        #[ValidationRules(minLength: 100, maxLength: 1000)]
        public $validationRulesStringMinMax;
        //#[ValidationRules(in: ['a', 'b', 'c'])]
        //public $validationRulesIn;
        #[ValidationRules(rules: 'string|max:1000')]
        public $validationRulesRules;
    }

    $rules = ReflectionReaderFactory::fromClass(DTOFromValidationRulesTest::class)
        ->getPropertiesValidationRules();

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
        ->getPropertiesValidationRules();

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
        ->getPropertiesValidationRules();

    $fnGetRuleIn = function (array $rules) {
        return Arr::first($rules, fn($rule) => (
            $rule instanceof RuleIn
            || (is_string($rule) && str_starts_with($rule, 'in:'))
        ));
    };

    $ruleIn = $fnGetRuleIn($rules['in']);
    expect($ruleIn)->not->toBeEmpty()
        ->and($ruleIn->__toString())->toBe('in:"a","b","c"');

    $ruleIn = $fnGetRuleIn($rules['inString']);
    expect($ruleIn)->toBe('in:a,b,c');
});

test('makeValueFromRawType with nullable', function () {
    $validationRules = new ValidationRules(nullable: true, arrayItem: new ValidationRules(string: true));
    $value = null;
    expect($validationRules->makeValueFromRawType($value))->toBe($value);
    $value = [1, 2];
    expect($validationRules->makeValueFromRawType($value))->toBe($value);
});
