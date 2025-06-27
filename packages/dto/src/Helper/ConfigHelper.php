<?php

namespace WebmanTech\DTO\Helper;

/**
 * @internal
 */
final class ConfigHelper
{
    private static array $testKV = [];

    /**
     * 获取配置
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null)
    {
        if (isset(self::$testKV[$key])) {
            return self::$testKV[$key];
        }

        return config("plugin.webman-tech.dto.{$key}", $default);
    }

    /**
     * 测试用
     */
    public static function setForTest(?string $key = null, mixed $value = null): void
    {
        if ($key === null) {
            // reset
            self::$testKV = [];
            return;
        }
        self::$testKV[$key] = $value;
    }
}
