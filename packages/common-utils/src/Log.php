<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Support\Facades\Log as LaravelLog;
use Psr\Log\LoggerInterface;
use support\Log as WebmanLog;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 日志相关
 */
final class Log
{
    /**
     * 获取 log 实例
     * @param string|null $name
     * @return LoggerInterface
     */
    public static function channel(?string $name = null): LoggerInterface
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_LOG_CHANNEL) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_LOG_CHANNEL, $name),
            Runtime::isWebman() => WebmanLog::channel($name ?? 'default'),
            Runtime::isLaravel() => LaravelLog::channel($name),
            default => throw new UnsupportedRuntime(),
        };
    }
}
