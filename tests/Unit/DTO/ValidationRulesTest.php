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

test('shallowValidation 不展开嵌套对象的验证规则', function () {
    // 定义嵌套 DTO
    class DTOShallowNestedObject extends BaseDTO
    {
        public string $street;
        public string $city;
    }

    // 不使用 shallowValidation 的 DTO
    class DTOWithNestedObject extends BaseDTO
    {
        public string $name;

        #[ValidationRules(object: DTOShallowNestedObject::class)]
        public ?DTOShallowNestedObject $address = null;
    }

    // 使用 shallowValidation 的 DTO
    class DTOWithShallowNestedObject extends BaseDTO
    {
        public string $name;

        #[ValidationRules(object: DTOShallowNestedObject::class, shallowValidation: true)]
        public ?DTOShallowNestedObject $address = null;
    }

    // 完整验证：应该展开嵌套规则
    $fullRules = DTOWithNestedObject::getValidationRules();
    expect($fullRules)->toHaveKey('address');
    expect($fullRules)->toHaveKey('address.street');
    expect($fullRules)->toHaveKey('address.city');
    expect($fullRules['address.street'])->toContain('required_with:address', 'string');
    expect($fullRules['address.city'])->toContain('required_with:address', 'string');

    // 浅层验证：应该只包含基础规则，不展开嵌套规则
    $shallowRules = DTOWithShallowNestedObject::getValidationRules();
    expect($shallowRules)->toHaveKey('address');
    expect($shallowRules['address'])->toContain('array', 'nullable');
    expect($shallowRules)->not->toHaveKey('address.street');
    expect($shallowRules)->not->toHaveKey('address.city');
});

test('shallowValidation 不展开数组项 DTO 的验证规则', function () {
    // 定义数组项 DTO
    class DTOShallowArrayItem extends BaseDTO
    {
        public string $title;
        public int $value;
    }

    // 不使用 shallowValidation 的 DTO
    class DTOWithArrayItem extends BaseDTO
    {
        public string $name;

        /**
         * @var array<DTOShallowArrayItem>
         */
        #[ValidationRules(arrayItem: DTOShallowArrayItem::class)]
        public array $items = [];
    }

    // 使用 shallowValidation 的 DTO
    class DTOWithShallowArrayItem extends BaseDTO
    {
        public string $name;

        /**
         * @var array<DTOShallowArrayItem>
         */
        #[ValidationRules(arrayItem: DTOShallowArrayItem::class, shallowValidation: true)]
        public array $items = [];
    }

    // 完整验证：应该展开数组项的嵌套规则
    $fullRules = DTOWithArrayItem::getValidationRules();
    expect($fullRules)->toHaveKey('items');
    expect($fullRules)->toHaveKey('items.*.title');
    expect($fullRules)->toHaveKey('items.*.value');
    expect($fullRules['items.*.title'])->toContain('required', 'string');
    expect($fullRules['items.*.value'])->toContain('required', 'integer');

    // 浅层验证：应该只包含基础规则，不展开数组项的嵌套规则
    $shallowRules = DTOWithShallowArrayItem::getValidationRules();
    expect($shallowRules)->toHaveKey('items');
    expect($shallowRules['items'])->toContain('array');
    expect($shallowRules)->not->toHaveKey('items.*.title');
    expect($shallowRules)->not->toHaveKey('items.*.value');
});

test('shallowValidation 不影响基础类型验证', function () {
    // 定义嵌套 DTO
    class DTOShallowNestedType extends BaseDTO
    {
        public string $field;
    }

    class DTOShallowBasicTypes extends BaseDTO
    {
        // 基础类型应该正常验证
        #[ValidationRules(shallowValidation: true)]
        public string $name;

        #[ValidationRules(shallowValidation: true)]
        public int $age;

        #[ValidationRules(shallowValidation: true)]
        public array $tags;

        #[ValidationRules(shallowValidation: true)]
        public bool $active;

        #[ValidationRules(shallowValidation: true)]
        public ?string $optional = null;
    }

    $rules = DTOShallowBasicTypes::getValidationRules();

    // 基础类型应该有验证规则
    expect($rules['name'])->toContain('required', 'string');
    expect($rules['age'])->toContain('required', 'integer');
    expect($rules['tags'])->toContain('required', 'array');
    expect($rules['active'])->toContain('required', 'boolean');
    expect($rules['optional'])->toContain('nullable', 'string');
});

test('shallowValidation 字段仍然正常进行数据赋值', function () {
    // 定义嵌套 DTO
    class DTOShallowNestedAssign extends BaseDTO
    {
        public string $title;
        public int $count;
    }

    class DTOShallowAssignTest extends BaseDTO
    {
        public string $name;

        #[ValidationRules(object: DTOShallowNestedAssign::class, shallowValidation: true)]
        public ?DTOShallowNestedAssign $nested = null;

        /**
         * @var array<DTOShallowNestedAssign>
         */
        #[ValidationRules(arrayItem: DTOShallowNestedAssign::class, shallowValidation: true)]
        public array $items = [];
    }

    // 测试数据赋值
    $dto = DTOShallowAssignTest::fromData([
        'name' => 'test',
        'nested' => [
            'title' => 'nested title',
            'count' => 10,
        ],
        'items' => [
            ['title' => 'item1', 'count' => 1],
            ['title' => 'item2', 'count' => 2],
        ],
    ]);

    expect($dto->name)->toBe('test');
    expect($dto->nested)->toBeInstanceOf(DTOShallowNestedAssign::class);
    expect($dto->nested->title)->toBe('nested title');
    expect($dto->nested->count)->toBe(10);
    expect($dto->items)->toHaveCount(2);
    expect($dto->items[0]->title)->toBe('item1');
    expect($dto->items[1]->count)->toBe(2);
});

test('三层嵌套对象的 required_with 应该包含完整路径', function () {
    // 定义三层嵌套 DTO
    class DTOTestLevel3 extends BaseDTO
    {
        public string $name;
        public int $value;
    }

    class DTOTestLevel2 extends BaseDTO
    {
        public string $title;
        public DTOTestLevel3 $level3;
    }

    class DTOTestLevel1 extends BaseDTO
    {
        public string $id;
        public DTOTestLevel2 $level2;
    }

    $rules = DTOTestLevel1::getValidationRules();

    // 第一层
    expect($rules)->toHaveKey('id');
    expect($rules['id'])->toContain('required', 'string');

    // 第二层
    expect($rules)->toHaveKey('level2');
    expect($rules)->toHaveKey('level2.title');
    expect($rules['level2.title'])->toContain('required_with:level2', 'string');

    // 第三层 - 这里应该是 'required_with:level2.level3' 而不是 'required_with:level3'
    expect($rules)->toHaveKey('level2.level3');
    expect($rules)->toHaveKey('level2.level3.name');
    expect($rules['level2.level3.name'])->toContain('required_with:level2.level3', 'string');
    expect($rules['level2.level3.value'])->toContain('required_with:level2.level3', 'integer');
});
