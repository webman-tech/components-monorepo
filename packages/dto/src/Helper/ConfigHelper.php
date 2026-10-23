<?php

namespace WebmanTech\DTO\Helper;

use function WebmanTech\CommonUtils\config;

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
        if (array_key_exists($key, self::$testKV)) {
            return self::$testKV[$key];
        }

        if (str_starts_with($key, 'dto.')) {
            return config(
                "plugin.webman-tech.dto.app.{$key}",
                static fn(): mixed => config("plugin.webman-tech.dto.{$key}", $default),
            );
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
