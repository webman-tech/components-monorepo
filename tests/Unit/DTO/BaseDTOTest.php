<?php

use Webman\Http\UploadFile;
use WebmanTech\DTO\Attributes\FromDataConfig;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;
use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOValidateException;
use WebmanTech\DTO\Helper\ConfigHelper;

test('fromData with validate', function () {
    class DTOFromDataWithValidateTest extends BaseDTO
    {
        public string $name;
        public int $age;
    }

    // 正常赋值
    $object = DTOFromDataWithValidateTest::fromData([
        'name' => 'nameValue',
        'age' => 18,
    ]);
    expect($object)->toBeInstanceOf(DTOFromDataWithValidateTest::class)
        ->and($object->name)->toBe('nameValue')
        ->and($object->age)->toBe(18);

    // 默认有 validation 时
    try {
        DTOFromDataWithValidateTest::fromData([
            'name' => 123,
            'age' => 'abc',
        ]);
        throw new InvalidArgumentException();
    } catch (DTOValidateException $e) {
        expect(array_keys($e->getErrors()))->toBe(['name', 'age'])
            ->and($e->first())->not->toBeEmpty();
    }

    // 无 validation 时
    try {
        DTOFromDataWithValidateTest::fromData([
            'name' => 123,
            'age' => 'abc',
        ], validate: false);
        throw new InvalidArgumentException();
    } catch (DTONewInstanceException $e) {
        expect($e->getClassName())->toBe(DTOFromDataWithValidateTest::class);
    }

    // 默认只返回验证过后的数据
    $dto = DTOFromDataWithValidateTest::fromData([
        'name' => 'aaa',
        'age' => 18,
        'child' => false,
    ]);
    expect($dto->toArray())->toBe([
        'name' => 'aaa',
        'age' => 18,
    ]);
});

test('fromData with extraValidateRules', function () {
    class DTOFromDataWithExtraValidateRulesTest extends BaseDTO
    {
        public string $name;
        public int $age;
        public string $page;
        public string $abc;

        protected static function getExtraValidationRules(): array
        {
            return [
                'name' => 'url',
                'age' => 'integer|max:10',
                'page' => function () {
                    return true;
                },
                'abc' => ['string', function () {
                    return true;
                }]
            ];
        }
    }

    // 不启用验证时可以赋值
    $dto = DTOFromDataWithExtraValidateRulesTest::fromData([
        'name' => 'name',
        'age' => 123,
        'page' => 'page',
        'abc' => 'abc',
    ], validate: false);
    expect($dto->name)->toBe('name');

    // 有验证时不行
    try {
        DTOFromDataWithExtraValidateRulesTest::fromData([
            'name' => 'name',
            'age' => 123,
            'page' => 'page',
            'abc' => 'abc',
        ]);
        throw new InvalidArgumentException();
    } catch (DTOValidateException $e) {
        expect(array_keys($e->getErrors()))->toBe(['name', 'age'])
            ->and($e->first())->not->toBeEmpty();
    }
});

test('fromData with validationRuleMessages', function () {
    class DTOFromDataWithValidationRuleMessagesTest extends BaseDTO
    {
        public string $name;
        public int $age;

        protected static function getValidationRuleMessages(): array
        {
            return [
                'name.required' => 'name is required',
                'age.integer' => 'age must be int',
            ];
        }
    }

    try {
        DTOFromDataWithValidationRuleMessagesTest::fromData([
            'age' => 'abc',
        ]);
        throw new InvalidArgumentException();
    } catch (DTOValidateException $e) {
        expect($e->getErrors())->toBe([
            'name' => [
                'name is required',
            ],
            'age' => [
                'age must be int',
            ],
        ]);
    }
});

test('fromData with ValidationRuleCustomAttributes', function () {
    class DTOFromDataWithValidationRuleCustomAttributesTest extends BaseDTO
    {
        public string $name;
        public int $age;

        protected static function getValidationRuleMessages(): array
        {
            return [
                'name.required' => ':attribute is required',
                'age.integer' => ':attribute must be int',
            ];
        }

        protected static function getValidationRuleCustomAttributes(): array
        {
            return [
                'name' => 'name_custom',
                'age' => 'age_custom',
            ];
        }
    }

    try {
        DTOFromDataWithValidationRuleCustomAttributesTest::fromData([
            'age' => 'abc',
        ]);
        throw new InvalidArgumentException();
    } catch (DTOValidateException $e) {
        expect($e->getErrors())->toBe([
            'name' => [
                'name_custom is required',
            ],
            'age' => [
                'age_custom must be int',
            ],
        ]);
    }
});

test('fromData use construct', function () {
    class DTOFromDataUseConstructTest extends BaseDTO
    {
        public int $x = 123; // 属性定义，有默认值

        public function __construct(
            public string     $name,
            public UploadFile $file, // 文件类型
                              $x, // 构造函数参数，无默认值
            public int        $age = 18,
            public            $y = 12, // 无类型定义，有默认值
        )
        {
        }
    }

    $uploadFile = new UploadFile('abc.txt', 'upload.txt', 'text/plain', 0);
    $dto = DTOFromDataUseConstructTest::fromData([
        'name' => 'nameValue',
        'x' => '111',
        'file' => $uploadFile,
    ]);
    expect($dto->name)->toBe('nameValue')
        ->and($dto->x)->toBe(123)
        ->and($dto->age)->toBe(18)
        ->and($dto->y)->toBe(12)
        ->and($dto->file)->toBe($uploadFile);
});

test('toArray with public properties', function () {
    $dto = new class extends BaseDTO {
        public string $name = 'nameValue';
        public int $int = 123;
        public ?int $null = null;
        public array $array = [1, 2, 3];
        public array $array2 = [
            'x' => 'y',
        ];

        public function __construct(
            public DateTime $dateTime = new DateTime('2025-12-12 11:11:11'),
        )
        {
        }

        protected string $protected = 'protectedValue';
    };

    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'int' => 123,
        'null' => null,
        'array' => [1, 2, 3],
        'array2' => [
            'x' => 'y',
        ],
        'dateTime' => (new DateTime('2025-12-12 11:11:11'))->format(DateTimeInterface::ATOM),
    ]);
});

test('toArray with special type', function () {
    $dto = new class extends BaseDTO {
        public function __construct(
            public DateTime              $dateTime = new DateTime('2025-12-12 11:11:11'),
            public RequestPropertyInEnum $requestPropertyInEnum = RequestPropertyInEnum::Json,
        )
        {
        }
    };

    ConfigHelper::setForTest('dto.to_array_default_datetime_format', 'Y-m-d H:i');

    expect($dto->toArray())->toBe([
        'dateTime' => '2025-12-12 11:11',
        'requestPropertyInEnum' => 'json',
    ]);

    ConfigHelper::setForTest();
});

test('toArray with ToArrayConfig', function () {
    // include
    #[ToArrayConfig(include: ['protected'])]
    class DTOToArrayWithToArrayConfigInclude extends BaseDTO
    {
        public string $name = 'nameValue';

        protected string $protected = 'protectedValue';
    }

    $dto = new DTOToArrayWithToArrayConfigInclude();
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'protected' => 'protectedValue',
    ]);

    // exclude
    #[ToArrayConfig(exclude: ['name2'])]
    class DTOToArrayWithToArrayConfigExclude extends BaseDTO
    {
        public string $name = 'nameValue';

        public string $name2 = 'protectedValue';
    }

    $dto = new DTOToArrayWithToArrayConfigExclude();
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
    ]);

    // only
    #[ToArrayConfig(only: ['name'])]
    class DTOToArrayWithToArrayConfigOnly extends BaseDTO
    {
        public string $name = 'nameValue';

        public string $name2 = 'protectedValue';
    }

    $dto = new DTOToArrayWithToArrayConfigOnly();
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
    ]);

    // 在 toArray 时传递 toArrayConfig（会覆盖类上的）
    $dto = new DTOToArrayWithToArrayConfigOnly();
    $toArrayConfig = new ToArrayConfig(only: ['name2']);
    expect($dto->toArray($toArrayConfig))->toBe([
        'name2' => 'protectedValue',
    ]);
});

test('toArray with ToArrayConfig ignoreNull', function () {
    class DTOToArrayWithToArrayConfigIgnoreNullChild extends BaseDTO
    {
        public ?string $name = null;
    }

    #[ToArrayConfig(ignoreNull: true)]
    class DTOToArrayWithToArrayConfigIgnoreNull extends BaseDTO
    {
        public string $name = 'nameValue';

        public ?string $name2 = null;

        public array $array = [
            'x' => 'x',
            'y' => null,
        ];

        public ?DTOToArrayWithToArrayConfigIgnoreNullChild $child = null;
    }

    // 忽略普通的 null
    $dto = new DTOToArrayWithToArrayConfigIgnoreNull();
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'array' => [
            'x' => 'x',
        ],
    ]);
    // 嵌套忽略
    $dto = DTOToArrayWithToArrayConfigIgnoreNull::fromData([
        'child' => [],
    ]);
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'array' => [
            'x' => 'x',
        ],
    ]);
    // 嵌套赋值
    $dto = DTOToArrayWithToArrayConfigIgnoreNull::fromData([
        'child' => [
            'name' => 'child',
        ],
    ]);
    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'array' => [
            'x' => 'x',
        ],
        'child' => [
            'name' => 'child',
        ],
    ]);
});

test('toArray with parent class', function () {
    class DTOToArrayWithParentDTO extends BaseDTO
    {
        public string $name = 'nameValue';
    }

    $dto = new class extends DTOToArrayWithParentDTO {
        public string $name2 = 'nameValue2';
    };

    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'name2' => 'nameValue2',
    ]);
});

test('toArray with nested type', function () {
    class DTOToArrayWithNestedDTO extends BaseDTO
    {
        public string $name = 'nameValue';
    }

    class DTOToArrayWithNestedDTO2 extends BaseDTO
    {
        public function __construct(
            public string                  $abc,
            public DTOToArrayWithNestedDTO $dto,
            public array                   $array,
            public array                   $arrayDTO,
        )
        {
        }
    }

    $dto = new DTOToArrayWithNestedDTO2(
        abc: 'xyz',
        dto: new DTOToArrayWithNestedDTO(),
        array: [
            'x' => 'y'
        ],
        arrayDTO: [
            new DTOToArrayWithNestedDTO(),
            new DTOToArrayWithNestedDTO(),
        ],
    );

    expect($dto->toArray())->toBe([
        'abc' => 'xyz',
        'dto' => [
            'name' => 'nameValue',
        ],
        'array' => [
            'x' => 'y'
        ],
        'arrayDTO' => [
            [
                'name' => 'nameValue',
            ],
            [
                'name' => 'nameValue',
            ],
        ],
    ]);
});

test('toArray with emptyArray', function () {
    #[ToArrayConfig(emptyArrayAsObject: true)]
    class DTOToArrayWithEmptyArray1 extends BaseDTO
    {
        public array $array = [];
    }

    $dto = new DTOToArrayWithEmptyArray1();
    expect($dto->toArray()['array'])->toBeInstanceOf(stdClass::class);

    #[ToArrayConfig(emptyArrayAsObject: ['arr1'])]
    class DTOToArrayWithEmptyArray2 extends BaseDTO
    {
        public array $arr1 = [];
        public array $arr2 = [];
    }

    $dto = new DTOToArrayWithEmptyArray2();
    $data = $dto->toArray();
    expect($data['arr1'])->toBeInstanceOf(stdClass::class)
        ->and($data['arr2'])->toBe([]);
});

test('toArray use singleKey', function () {
    #[ToArrayConfig(singleKey: 'list')]
    class DTOToArrayUseSingleKey extends BaseDTO
    {
        public array $list = [];
    }

    $dto = new DTOToArrayUseSingleKey();
    $dto->list = [['id' => 1], ['id' => 2]];
    expect($dto->toArray())->toBe($dto->list);
});

test('fromData with nested DTO validate', function () {
    // 子 DTO，有额外的验证规则
    class DTONestedChildValidateTrueTest extends BaseDTO
    {
        public static int $gotValidationRulesCount = 0;

        public string $name;
        public int $age;

        protected static function getExtraValidationRules(): array
        {
            return [
                'age' => 'min:18', // 额外的验证规则：age 必须 >= 18
            ];
        }

        public static function getValidationRules(): array
        {
            self::$gotValidationRulesCount++;

            return parent::getValidationRules();
        }
    }

    // 父 DTO，包含子 DTO 属性
    class DTONestedParentValidateTrueTest extends BaseDTO
    {
        public string $title;

        public DTONestedChildValidateTrueTest $child;

        /**
         * @var array|DTONestedChildValidateTrueTest[]
         */
        public array $children;
    }

    // 当 validate=true 时，父 DTO 的验证规则现在应该包含子 DTO 的额外规则（min:18）
    $parentRules = DTONestedParentValidateTrueTest::getValidationRules();
    expect($parentRules)->toBe([
        'title' => ['required', 'string'],
        'child' => ['required', 'array'],
        'child.name' => ['required_with:child', 'string'],
        'child.age' => ['required_with:child', 'integer', 'min:18'],
        'children' => ['required', 'array'],
        'children.*.name' => ['required', 'string'],
        'children.*.age' => ['required', 'integer', 'min:18'],
    ]);
    // 验证会在父层级完成，子 DTO 不会重复验证
    // 获取验证规则应该只被调用了2次: child 和 children 各 1 次
    expect(DTONestedChildValidateTrueTest::$gotValidationRulesCount)->toBe(2);

    try {
        DTONestedParentValidateTrueTest::fromData([
            'title' => 'parent',
            'child' => [
                'name' => 'child',
                'age' => 10, // 不满足 min:18 规则
            ],
        ]);
        throw new InvalidArgumentException('not reachable');
    } catch (DTOValidateException $e) {
        // 父层级的验证异常（现在父 DTO 会验证子 DTO 的额外规则）
        expect($e)->toBeInstanceOf(DTOValidateException::class)
            ->and($e->getErrors())->toHaveKey('child.age');
    }

    $data = DTONestedParentValidateTrueTest::fromData([
        'title' => 'parent',
        'child' => [
            'name' => 'child',
            'age' => 10, // 不满足 min:18 规则
        ],
    ], validate: false);
    // 能走到这证明没有验证，包括嵌套 DTO 的验证也没执行
    expect($data->child->age)->toBe(10);
});

test('fromData with FromDataConfig ignoreNull', function () {
    #[FromDataConfig(ignoreNull: true)]
    class DTOFromDataWithIgnoreNull extends BaseDTO
    {
        public function __construct(
            public string $name = 'kitty',
            public string $email = 'default@example.com',
        )
        {
        }
    }

    // 不开启验证时，忽略 null 值，使用默认值
    $dto = DTOFromDataWithIgnoreNull::fromData([
        'name' => null,
    ], validate: false);
    expect($dto->name)->toBe('kitty')
        ->and($dto->email)->toBe('default@example.com');

    // 开启验证时，null 被过滤掉，使用默认值
    $dto = DTOFromDataWithIgnoreNull::fromData([
        'name' => null,
    ], validate: true);
    expect($dto->name)->toBe('kitty')
        ->and($dto->email)->toBe('default@example.com');

    // 正常赋值（开启验证）
    $dto = DTOFromDataWithIgnoreNull::fromData([
        'name' => 'test',
    ], validate: true);
    expect($dto->name)->toBe('test')
        ->and($dto->email)->toBe('default@example.com');

    // 不带注解时，null 不会被忽略（开启验证）
    class DTOFromDataWithoutIgnoreNull extends BaseDTO
    {
        public function __construct(
            public ?string $name = 'kitty',
        )
        {
        }
    }

    $dto = DTOFromDataWithoutIgnoreNull::fromData([
        'name' => null,
    ], validate: true);
    expect($dto->name)->toBe(null);

    // 带注解时，没有配默认值时
    #[FromDataConfig(ignoreNull: true)]
    class DTOFromDataWithIgnoreNullNoDefault extends BaseDTO
    {
        public function __construct(
            public ?string $name,
        )
        {
        }
    }

    // 验证时，提示验证失败
    try {
        DTOFromDataWithIgnoreNullNoDefault::fromData([
            'name' => null,
        ], validate: true);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        expect($e->getMessage())->toContain('name');
    }

    // 不验证时，提示 new 失败
    try {
        DTOFromDataWithIgnoreNullNoDefault::fromData([
            'name' => null,
        ], validate: false);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTONewInstanceException $e) {
        expect($e->getMessage())->toContain('new DTOFromDataWithIgnoreNullNoDefault failed');
    }
});

test('fromData with FromDataConfig ignoreEmpty', function () {
    #[FromDataConfig(ignoreEmpty: true)]
    class DTOFromDataWithIgnoreEmpty extends BaseDTO
    {
        public function __construct(
            public string $name = 'default',
            public string $email = 'default@example.com',
        )
        {
        }
    }

    // 不开启验证时，忽略空字符串，使用默认值
    $dto = DTOFromDataWithIgnoreEmpty::fromData([
        'name' => '',
    ], validate: false);
    expect($dto->name)->toBe('default')
        ->and($dto->email)->toBe('default@example.com');

    // 开启验证时，空字符串被过滤掉，使用默认值
    $dto = DTOFromDataWithIgnoreEmpty::fromData([
        'name' => '',
    ], validate: true);
    expect($dto->name)->toBe('default')
        ->and($dto->email)->toBe('default@example.com');

    // 正常赋值（开启验证）
    $dto = DTOFromDataWithIgnoreEmpty::fromData([
        'name' => 'test',
    ], validate: true);
    expect($dto->name)->toBe('test');

    // 不带注解时，空字符串不会被忽略（开启验证）
    class DTOFromDataWithoutIgnoreEmpty extends BaseDTO
    {
        public function __construct(
            public string $name = 'default',
        )
        {
        }
    }

    $dto = DTOFromDataWithoutIgnoreEmpty::fromData([
        'name' => '',
    ], validate: true);
    expect($dto->name)->toBe('');

    // 带注解时，没有配默认值时
    #[FromDataConfig(ignoreEmpty: true)]
    class DTOFromDataWithIgnoreEmptyNoDefault extends BaseDTO
    {
        public function __construct(
            public string $name,
        )
        {
        }
    }

    // 验证时，提示验证失败
    try {
        DTOFromDataWithIgnoreEmptyNoDefault::fromData([
            'name' => '',
        ], validate: true);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        expect($e->getMessage())->toContain('name');
    }

    // 不验证时，提示 new 失败
    try {
        DTOFromDataWithIgnoreEmptyNoDefault::fromData([
            'name' => '',
        ], validate: false);
    } catch (DTONewInstanceException $e) {
        expect($e->getMessage())->toContain('new DTOFromDataWithIgnoreEmptyNoDefault failed');
    }
});

test('fromData with FromDataConfig ignoreNull and ignoreEmpty', function () {
    #[FromDataConfig(ignoreNull: true, ignoreEmpty: true)]
    class DTOFromDataWithIgnoreBoth extends BaseDTO
    {
        public function __construct(
            public ?string $name = 'default-name',
            public string  $email = 'default@example.com',
            public string  $phone = 'default-phone',
        )
        {
        }
    }

    // 不开启验证时，同时忽略 null 和空字符串
    $dto = DTOFromDataWithIgnoreBoth::fromData([
        'name' => null,
        'email' => '',
        'phone' => '123456',
    ], validate: false);
    expect($dto->name)->toBe('default-name')
        ->and($dto->email)->toBe('default@example.com')
        ->and($dto->phone)->toBe('123456');

    // 开启验证时，同时忽略 null 和空字符串
    $dto = DTOFromDataWithIgnoreBoth::fromData([
        'name' => null,
        'email' => '',
        'phone' => '123456',
    ], validate: true);
    expect($dto->name)->toBe('default-name')
        ->and($dto->email)->toBe('default@example.com')
        ->and($dto->phone)->toBe('123456');
});

test('fromData with FromDataConfig trim', function () {
    #[FromDataConfig(trim: true)]
    class DTOFromDataWithTrim extends BaseDTO
    {
        public function __construct(
            public string $name,
            public string $email,
            public int    $age,
        )
        {
        }
    }

    // trim 会去除字符串首尾空格
    $dto = DTOFromDataWithTrim::fromData([
        'name' => '  hello  ',
        'email' => '  test@example.com  ',
        'age' => 18,
    ], validate: false);
    expect($dto->name)->toBe('hello')
        ->and($dto->email)->toBe('test@example.com')
        ->and($dto->age)->toBe(18);

    // 非字符串类型不受影响
    $dto = DTOFromDataWithTrim::fromData([
        'name' => '  world  ',
        'email' => 'world@example.com',
        'age' => 25,
    ], validate: true);
    expect($dto->name)->toBe('world')
        ->and($dto->email)->toBe('world@example.com')
        ->and($dto->age)->toBe(25);

    // trim 和 ignoreEmpty 结合使用
    #[FromDataConfig(trim: true, ignoreEmpty: true)]
    class DTOFromDataWithTrimAndIgnoreEmpty extends BaseDTO
    {
        public function __construct(
            public string $name = 'default',
            public string $email = 'default@example.com',
        )
        {
        }
    }

    $dto = DTOFromDataWithTrimAndIgnoreEmpty::fromData([
        'name' => '   ', // trim 后为空字符串，被忽略
        'email' => '  test@example.com  ',
    ], validate: false);
    expect($dto->name)->toBe('default')
        ->and($dto->email)->toBe('test@example.com');

    // 不带注解时，字符串不会被 trim
    class DTOFromDataWithoutTrim extends BaseDTO
    {
        public function __construct(
            public string $name,
        )
        {
        }
    }

    $dto = DTOFromDataWithoutTrim::fromData([
        'name' => '  hello  ',
    ], validate: false);
    expect($dto->name)->toBe('  hello  ');
});

test('fromData with FromDataConfig validatePropertiesAllWithBail', function () {
    // 启用 validatePropertiesAllWithBail，给每个属性都添加 bail 验证（验证失败时停止该字段的后续验证）
    #[FromDataConfig(validatePropertiesAllWithBail: true)]
    class DTOFromDataWithValidatePropertiesAllWithBail extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(min: 100)]
            public string $name,
        )
        {
        }
    }

    // 检查验证规则是否包含 bail
    $rules = DTOFromDataWithValidatePropertiesAllWithBail::getValidationRules();
    foreach ($rules as $fieldRules) {
        expect($fieldRules[0])->toBe('bail');
    }

    // 验证失败时测试
    try {
        DTOFromDataWithValidatePropertiesAllWithBail::fromData([
            'name' => 123, // 类型错误
        ]);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        // bail 规则在第一个验证失败时停止，所以 name 字段只有第一个错误
        expect($e->getErrors()['name'])->toHaveCount(1); // 验证只有一个错误信息
    }

    // 对比不使用 bail 的情况
    class DTOFromDataWithoutBailForCompare extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(minLength: 100)]
            public string $name,
        )
        {
        }
    }

    try {
        DTOFromDataWithoutBailForCompare::fromData([
            'name' => 123, // 类型错误，会验证 string 规则和后续规则
        ]);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        // 没有 bail 时，name 字段可能有多个错误（取决于验证器行为）
        expect($e->getErrors()['name'])->toHaveCount(2); // 验证不止一个错误信息
    }

    // 不带注解时，不添加 bail 规则
    class DTOFromDataWithoutValidatePropertiesAllWithBail extends BaseDTO
    {
        public function __construct(
            public string $name,
        )
        {
        }
    }

    $rules = DTOFromDataWithoutValidatePropertiesAllWithBail::getValidationRules();
    foreach ($rules as $fieldRules) {
        expect($fieldRules[0])->not->toBe('bail');
    }
});

test('fromData with bail validation rule', function () {
    // 测试 bail 规则会被提取到最前面
    class DTOWithBailInRules extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(['bail', 'min:100'])]
            public string $name,
        )
        {
        }
    }

    $rules = DTOWithBailInRules::getValidationRules();
    expect($rules['name'][0])->toBe('bail'); // bail 在第一位
    expect($rules['name'])->toContain('required', 'string', 'min:100');

    // 统计 bail 数量，应该只有 1 个
    $bailCount = 0;
    foreach ($rules['name'] as $rule) {
        if (is_string($rule) && $rule === 'bail') {
            $bailCount++;
        }
    }
    expect($bailCount)->toBe(1); // 不会重复添加
});

test('fromData with FromDataConfig stopOnFirstFailure', function () {
    // 启用 validateStopOnFirstFailure，验证器在第一次失败时停止所有验证
    #[FromDataConfig(validateStopOnFirstFailure: true)]
    class DTOWithStopOnFirstFailure extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(min: 5)]
            public string $name,
            public string $email,
            public int $age,
        ) {}
    }

    // 验证失败时测试 - 第一个字段失败后立即停止
    try {
        DTOWithStopOnFirstFailure::fromData([
            'name' => 'ab',  // 不满足 min:5（第一个字段）
            'email' => 'invalid-email',  // 不会验证这个
            'age' => 'not-a-number',  // 不会验证这个
        ]);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        // 只有 name 字段的错误
        expect($e->getErrors())->toHaveKey('name');
        expect($e->getErrors())->not->toHaveKey('email');
        expect($e->getErrors())->not->toHaveKey('age');
    }

    // 对比：不使用 stopOnFirstFailure 的情况
    class DTOWithoutStopOnFirstFailure extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(min: 5)]
            public string $name,
            public string $email,
            #[ValidationRules(min: 18)]
            public int $age,
        ) {}
    }

    try {
        DTOWithoutStopOnFirstFailure::fromData([
            'name' => 'ab',  // 不满足 min:5
            'email' => 123,  // 类型错误
            'age' => 10,  // 不满足 min:18
        ]);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        // 会验证所有字段，返回所有错误
        expect($e->getErrors())->toHaveKey('name');
        expect($e->getErrors())->toHaveKey('email');
        expect($e->getErrors())->toHaveKey('age');
    }

    // validateStopOnFirstFailure 与 bail 配合使用
    #[FromDataConfig(validateStopOnFirstFailure: true, validatePropertiesAllWithBail: true)]
    class DTOWithStopAndBail extends BaseDTO
    {
        public function __construct(
            #[ValidationRules(min: 100)]
            public string $name,
            #[ValidationRules(min: 5)]
            public string $email,
        ) {}
    }

    try {
        DTOWithStopAndBail::fromData([
            'name' => 123,  // 类型错误
            'email' => 'ab',  // 不满足 min:5，但不会验证
        ]);
        throw new InvalidArgumentException('Not reachable');
    } catch (DTOValidateException $e) {
        // bail 让 name 只有一个错误，stopOnFirstFailure 让后续字段不验证
        expect($e->getErrors())->toHaveKey('name');
        expect($e->getErrors())->not->toHaveKey('email');
        expect($e->getErrors()['name'])->toHaveCount(1); // bail 只返回第一个错误
    }
});
