<?php

use WebmanTech\DTO\Reflection\ReflectionReaderFactory;

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
