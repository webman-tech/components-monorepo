<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Contracts\Foundation\Application as LaravelApplication;
use Webman\App as WebmanApp;
use Workerman\Worker;

/**
 * 运行环境相关
 */
final class Runtime
{
    public static ?string $RUNTIME = null; // 用于固定指定 runtime

    private static ?bool $isWorkerman = null;

    /**
     * 是否在 workerman 环境下
     * @return bool
     */
    public static function isWorkerman(): bool
    {
        if (self::$isWorkerman === null) {
            if (self::$RUNTIME !== null) {
                self::$isWorkerman = in_array(self::$RUNTIME, [Constants::RUNTIME_WORKERMAN, Constants::RUNTIME_WEBMAN]);
            } else {
                self::$isWorkerman = class_exists(Worker::class)
                    && Worker::getStatus() === Worker::STATUS_RUNNING;
            }
        }
        return self::$isWorkerman;
    }

    private static ?bool $isWebman = null;

    /**
     * 是否在 webman 下
     * @return bool
     */
    public static function isWebman(): bool
    {
        if (self::$isWebman === null) {
            if (self::$RUNTIME !== null) {
                self::$isWebman = self::$RUNTIME === Constants::RUNTIME_WEBMAN;
            } else {
                self::$isWebman = class_exists(WebmanApp::class);
            }
        }
        return self::$isWebman;
    }

    private static ?bool $isLaravel = null;

    /**
     * 是否是 laravel
     * @return bool
     */
    public static function isLaravel(): bool
    {
        if (self::$isLaravel === null) {
            if (self::$RUNTIME !== null) {
                self::$isLaravel = self::$RUNTIME === Constants::RUNTIME_LARAVEL;
            } else {
                self::$isLaravel = interface_exists(LaravelApplication::class)
                    && function_exists('app')
                    && \app() instanceof LaravelApplication;
            }
        }
        return self::$isLaravel;
    }

    /**
     * 是否是自定义环境
     * @return bool
     */
    public static function isCustom(): bool
    {
        return self::$RUNTIME === Constants::RUNTIME_CUSTOM;
    }

    /**
     * 是否在 cli 环境下
     * 注意：workerman 时此值也是 true
     * @return bool
     */
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    private static string|false|null $os = false;

    /**
     * 获取当前操作系统类型
     * @return string|null
     */
    public static function getOS(): ?string
    {
        if (self::$os === false) {
            $os = php_uname('s');

            if (str_contains($os, 'Linux')) {
                $os = 'linux';
            } elseif (str_contains($os, 'Darwin')) {
                $os = 'osx';
            } elseif (str_contains($os, 'Windows')) {
                $os = 'windows';
            } else {
                $os = null;
            }

            self::$os = $os;
        }

        return self::$os;
    }

    /**
     * 单次请求结束时的回调
     * @param callable $fn
     * @return void
     */
    public static function terminating(callable $fn): void
    {
        if (self::isLaravel()) {
            /** @phpstan-ignore function.notFound */
            app()->terminating($fn);
        } elseif (function_exists('terminating')) {
            terminating($fn);
        }
        // 不支持的不做任何处理
    }
}
