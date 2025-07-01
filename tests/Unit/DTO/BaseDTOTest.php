<?php

use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Exceptions\DTONewInstanceException;
use WebmanTech\DTO\Exceptions\DTOValidateException;

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

        protected static function getExtraValidationRules(): array
        {
            return [
                'name' => 'url',
                'age' => 'integer|max:10',
            ];
        }
    }

    // 不启用验证时可以赋值
    $dto = DTOFromDataWithExtraValidateRulesTest::fromData([
        'name' => 'name',
        'age' => 123,
    ], validate: false);
    expect($dto->name)->toBe('name');

    // 有验证时不行
    try {
        DTOFromDataWithExtraValidateRulesTest::fromData([
            'name' => 'name',
            'age' => 123,
        ]);
        throw new InvalidArgumentException();
    } catch (DTOValidateException $e) {
        expect(array_keys($e->getErrors()))->toBe(['name', 'age'])
            ->and($e->first())->not->toBeEmpty();
    }
});

test('toArray with public properties', function () {
    $dto = new class extends BaseDTO {
        public string $name = 'nameValue';
        public int $int = 123;
        public ?int $null = null;

        protected string $protected = 'protectedValue';
    };

    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'int' => 123,
        'null' => null
    ]);
});

test('toArray with include properties', function () {
    $dto = new class extends BaseDTO {
        public string $name = 'nameValue';

        protected string $protected = 'protectedValue';

        protected function getToArrayIncludeProperties(): array
        {
            return ['protected'];
        }
    };

    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
        'protected' => 'protectedValue',
    ]);
});

test('toArray with exclude properties', function () {
    $dto = new class extends BaseDTO {
        public string $name = 'nameValue';
        public string $name2 = 'nameValue2';

        protected function getToArrayExcludeProperties(): array
        {
            return ['name2'];
        }
    };

    expect($dto->toArray())->toBe([
        'name' => 'nameValue',
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
