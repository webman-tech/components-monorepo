<?php

namespace WebmanTech\CommonUtils\Testing;

use Webman\Container;

final class TestContainer extends Container
{
    private static ?Container $instance = null;

    public static function instance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new Container();
        }

        return self::$instance;
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
