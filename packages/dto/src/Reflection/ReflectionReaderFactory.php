<?php

namespace WebmanTech\DTO\Reflection;

use ReflectionClass;

/**
 * @internal
 */
final class ReflectionReaderFactory
{
    public static function fromClass(string|object $class): ReflectionClassReader
    {
        return self::instance()->parseFromClass($class);
    }

    public static function fromReflectionClass(ReflectionClass $reflectionClass): ReflectionClassReader
    {
        return self::instance()->parseFromReflectionClass($reflectionClass);
    }

    private static self $instance;

    private static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private array $cache = [];

    private function parseFromClass(string|object $class): ReflectionClassReader
    {
        $className = is_object($class) ? $class::class : $class;
        if (!isset($this->cache[$className])) {
            $this->cache[$className] = new ReflectionClassReader(new ReflectionClass($class));
        }
        return $this->cache[$className];
    }

    private function parseFromReflectionClass(ReflectionClass $reflectionClass): ReflectionClassReader
    {
        $className = $reflectionClass->getName();
        if (!isset($this->cache[$className])) {
            $this->cache[$className] = new ReflectionClassReader($reflectionClass);
        }
        return $this->cache[$className];
    }
}
