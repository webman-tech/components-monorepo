<?php

namespace WebmanTech\CommonUtils;

use Psr\Log\LoggerInterface;

/**
 * 设置 env
 */
function put_env(string $key, mixed $value): void
{
    EnvAttr::set($key, $value);
}

/**
 * 获取 env
 */
function get_env(string $key, mixed $default = null, array $whichIsNull = [null, '']): mixed
{
    return EnvAttr::get($key, $default, $whichIsNull);
}

/**
 * 获取项目根路径
 */
function base_path(string $path = ''): string
{
    return Local::getBasePath($path);
}

/**
 * 获取项目 runtime 路径
 */
function runtime_path(string $path = ''): string
{
    return Local::getRuntimePath($path);
}

/**
 * 获取项目 config 路径
 */
function config_path(string $path = ''): string
{
    return Local::getConfigPath($path);
}

/**
 * 获取项目 public 静态资源路径
 */
function app_path(string $path = ''): string
{
    return Local::getAppPath($path);
}

/**
 * 获取项目 vendor 路径
 */
function vendor_path(string $path = ''): string
{
    return Local::getVendorPath($path);
}

/**
 * 获取配置
 */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/**
 * 获取 Log
 */
function logger(?string $channel = null): LoggerInterface
{
    return Log::channel($channel);
}

/**
 * 从容器中获取组件
 * @template TClass of object
 * @param string|class-string<TClass> $name
 * @return ($name is class-string<TClass> ? TClass : mixed))
 */
function container_get(string $name): mixed
{
    return Container::get($name);
}

/**
 * 容器中是否存在组件
 */
function container_has(string $name): bool
{
    return Container::has($name);
}

/**
 * 用容器创建组件
 * @template TClass of object
 * @param string|class-string<TClass> $name
 * @param array $parameters
 * @return ($name is class-string<TClass> ? TClass : mixed))
 */
function container_make(string $name, array $parameters = []): mixed
{
    return Container::make($name, $parameters);
}

/**
 * 获取/设置 语言
 */
function locale(?string $locale = null): string
{
    if ($locale === null) {
        return Lang::getLocale();
    }
    Lang::setLocale($locale);
    return $locale;
}
