<?php

namespace WebmanTech\CommonUtils\Testing;

use Webman\Config;

final class TestConfig extends Config
{
    private static array $mockValues = [];

    /**
     * 测试环境下支持模拟配置 config
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addMock(string $key, mixed $value): void
    {
        self::$mockValues[$key] = $value;
    }

    /**
     * 重置 mock
     * @return void
     */
    public static function resetTestMock(): void
    {
        self::$mockValues = [];
    }

    public static function staticGet(string $key, mixed $default = null): mixed
    {
        if (isset(self::$mockValues[$key])) {
            return self::$mockValues[$key];
        }

        return self::get($key, $default);
    }
}
