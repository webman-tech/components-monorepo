<?php

namespace WebmanTech\CommonUtils;

use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 容器组件
 */
final class Container
{
    /**
     * 获取组件
     * @template TClass of object
     * @param string|class-string<TClass> $name
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    public static function get(string $name): mixed
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONTAINER_GET) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONTAINER_GET, $name),
            Runtime::isWebman() => \support\Container::get($name),
            Runtime::isLaravel() => \Illuminate\Container\Container::getInstance()->get($name),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 容器中是否存在
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONTAINER_HAS) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONTAINER_HAS, $name),
            Runtime::isWebman() => \support\Container::has($name),
            Runtime::isLaravel() => \Illuminate\Container\Container::getInstance()->has($name),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 构建组件
     * @template TClass of object
     * @param string|class-string<TClass> $name
     * @param array $parameters
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    public static function make(string $name, array $parameters = []): mixed
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONTAINER_MAKE) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONTAINER_MAKE, $name, $parameters),
            Runtime::isWebman() => \support\Container::make($name, $parameters),
            Runtime::isLaravel() => \Illuminate\Container\Container::getInstance()->make($name, $parameters),
            default => throw new UnsupportedRuntime(),
        };
    }
}
