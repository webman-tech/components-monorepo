<?php

namespace WebmanTech\CommonUtils;

use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 容器组件
 */
final class Container
{
    private static mixed $instance = null;

    private static function instance()
    {
        if (self::$instance === null) {
            self::$instance = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONTAINER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONTAINER),
                Runtime::isWebman() => \support\Container::instance(),
                Runtime::isLaravel() => \Illuminate\Container\Container::getInstance(),
                default => throw new UnsupportedRuntime(),
            };
        }
        return self::$instance;
    }

    /**
     * 获取组件
     * @template TClass of object
     * @param string|class-string<TClass> $name
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    public static function get(string $name): mixed
    {
        return self::instance()->get($name);
    }

    /**
     * 容器中是否存在
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::instance()->has($name);
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
        return self::instance()->make($name, $parameters);
    }
}
