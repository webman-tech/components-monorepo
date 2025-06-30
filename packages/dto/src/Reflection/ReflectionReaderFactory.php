<?php

namespace WebmanTech\DTO\Reflection;

use ReflectionClass;

/**
 * @internal
 */
final class ReflectionReaderFactory
{
    public static function fromClass(string $className): ReflectionClassReader
    {
        return self::fromReflectionClass(new ReflectionClass($className));
    }

    public static function fromReflectionClass(ReflectionClass $reflectionClass): ReflectionClassReader
    {
        return new ReflectionClassReader($reflectionClass);
    }
}
