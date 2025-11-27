<?php

namespace WebmanTech\CommonUtils;

/**
 * 自定义环境相关组件实现的注册
 */
final class RuntimeCustomRegister
{
    public const KEY_BASE_PATH = 'base_path';
    public const KEY_RUNTIME_PATH = 'runtime_path';
    public const KEY_CONFIG_PATH = 'config_path';
    public const KEY_APP_PATH = 'app_path';
    public const KEY_VENDOR_PATH = 'vendor_path';
    public const KEY_CONFIG_GET = 'config_get';
    public const KEY_CONTAINER = 'container';
    public const KEY_LOG_CHANNEL = 'log_channel';
    public const KEY_LANG_GET_LOCALE = 'lang_get_locale';
    public const KEY_LANG_SET_LOCALE = 'lang_set_locale';
    public const KEY_REQUEST = 'request';
    public const KEY_RESPONSE = 'response';
    public const KEY_SESSION = 'session';
    public const KEY_ROUTE = 'route';

    /**
     * @var array<self::KEY_*, \Closure>
     */
    private static array $repository = [];

    /**
     * 注册
     * @param self::KEY_* $key
     */
    public static function register(string $key, \Closure|null $value): void
    {
        if ($value === null) {
            unset(self::$repository[$key]);
            return;
        }
        self::$repository[$key] = $value;
    }

    /**
     * 获取注册的闭包
     */
    public static function getRegistered(string $key): ?\Closure
    {
        return self::$repository[$key] ?? null;
    }

    /**
     * 是否注册过
     * @param string $key
     * @return bool
     */
    public static function isRegistered(string $key): bool
    {
        return isset(self::$repository[$key]);
    }

    /**
     * 调用
     * @param self::KEY_* $key
     */
    public static function call(string $key, mixed ...$args): mixed
    {
        if (!isset(self::$repository[$key])) {
            throw new \InvalidArgumentException("RuntimeCustomRegister: $key not registered");
        }

        return self::$repository[$key](...$args);
    }
}
