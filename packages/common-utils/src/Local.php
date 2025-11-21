<?php

namespace WebmanTech\CommonUtils;

use Symfony\Component\Process\Process;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 本地路径相关
 */
final class Local
{
    private static ?string $basePath = null;
    private static ?string $runtimePath = null;
    private static ?string $configPath = null;
    private static ?string $appPath = null;
    private static ?string $vendorPath = null;

    /**
     * 获取项目基本路径
     */
    public static function getBasePath(string $path = ''): string
    {
        if (self::$basePath === null) {
            self::$basePath = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_BASE_PATH) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_BASE_PATH),
                Runtime::isWebman() => \base_path(),
                Runtime::isLaravel() => \base_path(),
                default => throw new UnsupportedRuntime(),
            };
        }
        return self::combinePath((string)self::$basePath, $path);
    }

    /**
     * 获取项目 runtime 路径
     */
    public static function getRuntimePath(string $path = ''): string
    {
        if (self::$runtimePath === null) {
            self::$runtimePath = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_RUNTIME_PATH) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_RUNTIME_PATH),
                Runtime::isWebman() => \runtime_path(),
                /** @phpstan-ignore function.notFound */
                Runtime::isLaravel() => \storage_path(),
                default => throw new UnsupportedRuntime(),
            };
        }
        return self::combinePath((string)self::$runtimePath, $path);
    }

    /**
     * 获取项目 config 路径
     */
    public static function getConfigPath(string $path = ''): string
    {
        if (self::$configPath === null) {
            self::$configPath = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONFIG_PATH) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONFIG_PATH),
                Runtime::isWebman() => \config_path(),
                Runtime::isLaravel() => \config_path(),
                default => throw new UnsupportedRuntime(),
            };
        }

        return self::combinePath((string)self::$configPath, $path);
    }

    /**
     * 获取 App 路径
     */
    public static function getAppPath(string $path = ''): string
    {
        if (self::$appPath === null) {
            self::$appPath = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_APP_PATH) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_APP_PATH),
                Runtime::isWebman() => \app_path(),
                Runtime::isLaravel() => \app_path(),
                default => throw new UnsupportedRuntime(),
            };
        }
        return self::combinePath((string)self::$appPath, $path);
    }

    /**
     * 获取 vendor 路径
     */
    public static function getVendorPath(string $path = ''): string
    {
        if (self::$vendorPath === null) {
            self::$vendorPath = match (true) {
                RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_VENDOR_PATH) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_VENDOR_PATH),
                Runtime::isWebman() => \base_path() . '/vendor',
                Runtime::isLaravel() => \base_path() . '/vendor',
                default => throw new UnsupportedRuntime(),
            };
        }
        return self::combinePath((string)self::$vendorPath, $path);
    }

    /**
     * 拼接 path
     * @param string $front
     * @param string ...$backs
     * @return string
     */
    public static function combinePath(string $front, string ...$backs): string
    {
        $path = $front;
        foreach ($backs as $back) {
            $path .= ($back ? (DIRECTORY_SEPARATOR . ltrim($back, DIRECTORY_SEPARATOR)) : $back);
        }
        return $path;
    }

    private static ?string $localIp = null;

    /**
     * 获取本机 ip
     * @param bool $refresh
     * @return string
     */
    public static function getIp(bool $refresh = false): string
    {
        if (self::$localIp !== null && !$refresh) {
            return self::$localIp;
        }

        if ($ip = get_env(Constants::ENV_LOCAL_IP)) {
            return self::$localIp = (string)$ip;
        }

        $fn = function () {
            $os = Runtime::getOS();
            if ($os === 'windows') {
                $process = Process::fromShellCommandline('ipconfig | findstr /i "IPv4"');
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException('获取本机IP失败，请手动指定');
                }
                $output = $process->getOutput();
                preg_match_all('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $output, $matches);
                if (!isset($matches[0][0])) {
                    throw new \RuntimeException('获取本机IP失败，请手动指定');
                }

                return $matches[0][0];
            }
            if ($os === 'linux') {
                $process = Process::fromShellCommandline("ip address show eth0 | head -n4 | grep inet | awk '{print$2}' | awk -F '/' '{print $1}'");
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException('获取本机IP失败，请手动指定');
                }

                return trim($process->getOutput());
            }
            if ($os === 'osx') {
                $process = Process::fromShellCommandline("ifconfig | grep 'inet ' | grep -v 127.0.0.1 | awk '{print $2}' | head -n 1");
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException('获取本机IP失败，请手动指定');
                }

                return trim($process->getOutput());
            }

            throw new \RuntimeException('不支持的操作系统，请手动指定 ip');
        };

        return self::$localIp = $fn();
    }
}
