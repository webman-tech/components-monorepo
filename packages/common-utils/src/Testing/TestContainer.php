<?php

namespace WebmanTech\CommonUtils\Testing;

use Webman\Container;

final class TestContainer
{
    private static ?Container $instance = null;

    public static function instance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new Container();
        }

        return self::$instance;
    }

    public static function get(string $name): mixed
    {
        return self::instance()->get($name);
    }

    public static function has(string $name): bool
    {
        return self::instance()->has($name);
    }

    public static function make(string $name, array $parameters = []): mixed
    {
        return self::instance()->make($name, $parameters);
    }

    public static function clear(): void
    {
        self::$instance = null;
    }

    public static function addSingleton(string $key, \Closure $value): void
    {
        self::instance()->addDefinitions([$key => $value]);
    }
}
