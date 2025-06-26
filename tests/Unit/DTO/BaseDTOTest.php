<?php

use WebmanTech\DTO\BaseDTO;

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
