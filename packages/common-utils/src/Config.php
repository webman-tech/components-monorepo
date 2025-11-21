<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Support\Str;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 配置相关
 */
final class Config
{
    /**
     * 获取 config 配置
     * @param string $key 支持 abc.xyz 的形式
     * @param mixed $default 支持 callback
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $defaultFn = null;
        if ($default instanceof \Closure) {
            $defaultFn = $default;
            $default = '__RN__' . Str::random();
        }

        $value = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONFIG_GET) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONFIG_GET, $key, $default),
            Runtime::isWebman() => \Webman\Config::get($key, $default),
            Runtime::isLaravel() => \Illuminate\Support\Facades\Config::get($key, $default),
            default => throw new UnsupportedRuntime(),
        };
        
        if ($defaultFn && $value === $default) {
            return $defaultFn();
        }
        return $value;
    }

    /**
     * 从 config 目录中 require 文件
     * @param string $filename
     * @return array
     */
    public static function requireFromConfigPath(string $filename): array
    {
        if (!str_ends_with($filename, '.php')) {
            $filename .= '.php';
        }
        return require Local::getConfigPath($filename);
    }
}
