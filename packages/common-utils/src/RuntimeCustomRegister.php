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

    /**
     * @var array<self::KEY_*, \Closure>
     */
    private static array $repository = [];

    /**
     * 注册
     * @param self::KEY_* $key
     */
    public static function register(string $key, \Closure $value): void
    {
        self::$repository[$key] = $value;
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
