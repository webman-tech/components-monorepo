<?php

use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

test('ReflectionReaderFactory instance', function () {
    class DTOFromReflectionReaderFactoryInstanceTest
    {
        public string $name;
        public ?int $age = 18;
        protected string $password2;
    }

    $reflectionClassReader = ReflectionReaderFactory::fromClass(DTOFromReflectionReaderFactoryInstanceTest::class);
    $reflectionClassReaderRepeat = ReflectionReaderFactory::fromClass(DTOFromReflectionReaderFactoryInstanceTest::class);
    $reflectionClassReaderReflectionClass = ReflectionReaderFactory::fromReflectionClass(new ReflectionClass(DTOFromReflectionReaderFactoryInstanceTest::class));

    expect($reflectionClassReader)
        ->toEqual($reflectionClassReaderRepeat)
        ->toEqual($reflectionClassReaderReflectionClass);
});

test('ReflectionClassReader getPublicPropertiesName', function () {
    class DTOFromReflectionClassReaderTestParent
    {
        public string $parent;
        public int $parentId;
        protected string $password;
    }

    class DTOFromReflectionClassReaderTest extends DTOFromReflectionClassReaderTestParent
    {
        public string $name;
        public ?int $age = 18;
        protected string $password2;
    }

    $reflectionClassReader = ReflectionReaderFactory::fromClass(DTOFromReflectionClassReaderTest::class);

    expect($reflectionClassReader->getPublicPropertiesName())->toBe(['parent', 'parentId', 'name', 'age']);
});

test('ReflectionClassReader getPublicPropertiesName speed', function () {
    class  DTOFromReflectionClassReaderGetPublicPropertiesNameSpeedTest
    {
        public string $name;
        public ?int $age = 18;
        protected string $password2;
    }

    $startAt = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        $reflectionClassReader = ReflectionReaderFactory::fromClass(DTOFromReflectionClassReaderGetPublicPropertiesNameSpeedTest::class);
        $reflectionClassReader->getPublicPropertiesName();
    }
    $endAt = microtime(true);
    expect($endAt - $startAt)->toBeLessThan(0.1);
});

test('ReflectionClassReader newInstanceByData', function () {
    enum DTOFromReflectionClassReaderNewInstanceByDataTestEnum: string
    {
        case A = 'a';
        case B = 'b';
    }

    class DTOFromReflectionClassReaderNewInstanceByDataTestItem
    {
        public function __construct(
            public string $name,
        )
        {
        }
    }

    class DTOFromReflectionClassReaderNewInstanceByDataTest
    {
        public string $propertyString;
        public int $propertyInt;
        public bool $propertyBool;
        public array $propertyArray;
        public DTOFromReflectionClassReaderNewInstanceByDataTestEnum $propertyEnum;
        public DTOFromReflectionClassReaderNewInstanceByDataTestItem $propertyObject;
        #[ValidationRules(arrayItem: DTOFromReflectionClassReaderNewInstanceByDataTestItem::class)]
        public array $propertyArrayWithItem;

        public function __construct(
            public string                                                $string,
            public int                                                   $int,
            public bool                                                  $bool,
            public array                                                 $array,
            public DTOFromReflectionClassReaderNewInstanceByDataTestEnum $enum,
            public DTOFromReflectionClassReaderNewInstanceByDataTestItem $object,
            #[ValidationRules(arrayItem: DTOFromReflectionClassReaderNewInstanceByDataTestItem::class)]
            public array                                                 $arrayWithItem,
            public string                                                $stringWithDefault = 'default',
        )
        {
        }
    }

    $obj = ReflectionReaderFactory::fromClass(DTOFromReflectionClassReaderNewInstanceByDataTest::class)->newInstanceByData([
        'string' => 'string',
        'int' => 1,
        'bool' => true,
        'array' => [1, 2, 3],
        'enum' => 'a',
        'object' => ['name' => 'abc'],
        'arrayWithItem' => [['name' => 'abc'], ['name' => 'def']],
        'propertyString' => 'propertyString',
        'propertyInt' => 2,
        'propertyBool' => false,
        'propertyArray' => [1, 2, 3],
        'propertyEnum' => 'b',
        'propertyObject' => ['name' => 'abc'],
        'propertyArrayWithItem' => [['name' => 'abc'], ['name' => 'def']],
    ]);
    expect($obj)->toBeInstanceOf(DTOFromReflectionClassReaderNewInstanceByDataTest::class)
        ->and($obj->string)->toBe('string')
        ->and($obj->int)->toBe(1)
        ->and($obj->bool)->toBeTrue()
        ->and($obj->array)->toBe([1, 2, 3])
        ->and($obj->enum)->toBe(DTOFromReflectionClassReaderNewInstanceByDataTestEnum::A)
        ->and($obj->object)->toBeInstanceOf(DTOFromReflectionClassReaderNewInstanceByDataTestItem::class)
        ->and($obj->object->name)->toBe('abc')
        ->and(count($obj->arrayWithItem))->toBe(2)
        ->and($obj->arrayWithItem[0]->name)->toBe('abc')
        ->and($obj->arrayWithItem[1]->name)->toBe('def')
        ->and($obj->stringWithDefault)->toBe('default')
        ->and($obj->propertyString)->toBe('propertyString')
        ->and($obj->propertyInt)->toBe(2)
        ->and($obj->propertyBool)->toBeFalse()
        ->and($obj->propertyArray)->toBe([1, 2, 3])
        ->and($obj->propertyEnum)->toBe(DTOFromReflectionClassReaderNewInstanceByDataTestEnum::B)
        ->and($obj->propertyObject)->toBeInstanceOf(DTOFromReflectionClassReaderNewInstanceByDataTestItem::class)
        ->and($obj->propertyObject->name)->toBe('abc')
        ->and(count($obj->propertyArrayWithItem))->toBe(2)
        ->and($obj->propertyArrayWithItem[0]->name)->toBe('abc')
        ->and($obj->propertyArrayWithItem[1]->name)->toBe('def');
});
