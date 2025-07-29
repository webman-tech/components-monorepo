<?php

use Webman\Http\UploadFile;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\BaseDTO;
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

test('toArray with dateTimeFormat', function () {
    $dto = new class extends BaseDTO {
        public function __construct(
            public DateTime $dateTime = new DateTime('2025-12-12 11:11:11'),
        )
        {
        }
    };

    ConfigHelper::setForTest('dto.to_array_default_datetime_format', 'Y-m-d H:i');

    expect($dto->toArray())->toBe([
        'dateTime' => '2025-12-12 11:11',
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
