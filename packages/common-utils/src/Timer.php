<?php

declare(strict_types=1);

namespace WebmanTech\CommonUtils;

use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use WebmanTech\CommonUtils\Timer\PcntlTimer;
use Workerman\Timer as WorkermanTimer;

/**
 * 定时器抽象
 */
final class Timer
{
    /**
     * 添加一个定时器
     * @param float $interval 间隔时间（秒）
     * @param callable $func 回调函数
     * @param array $args 回调函数参数
     * @param bool $persistent 是否持久化（true 为循环定时器，false 为单次定时器）
     * @return int 定时器 ID
     */
    public static function add(float $interval, callable $func, array $args = [], bool $persistent = true): int
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_TIMER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_TIMER, 'add', $interval, $func, $args, $persistent),
            Runtime::isWorkerman() => WorkermanTimer::add($interval, $func, $args, $persistent),
            self::isCliWithPcntl() => PcntlTimer::add($interval, $func, $args, $persistent),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 添加一个单次延时定时器
     * @param float $interval 延迟时间（秒）
     * @param callable $func 回调函数
     * @param array $args 回调函数参数
     * @return int 定时器 ID
     */
    public static function delay(float $interval, callable $func, array $args = []): int
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_TIMER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_TIMER, 'delay', $interval, $func, $args),
            Runtime::isWorkerman() => WorkermanTimer::add($interval, $func, $args, false),
            self::isCliWithPcntl() => PcntlTimer::add($interval, $func, $args, false),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 添加一个循环定时器
     * @param float $interval 间隔时间（秒）
     * @param callable $func 回调函数
     * @param array $args 回调函数参数
     * @return int 定时器 ID
     */
    public static function repeat(float $interval, callable $func, array $args = []): int
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_TIMER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_TIMER, 'repeat', $interval, $func, $args),
            Runtime::isWorkerman() => WorkermanTimer::add($interval, $func, $args, true),
            self::isCliWithPcntl() => PcntlTimer::add($interval, $func, $args, true),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 删除一个定时器
     * @param int $timerId 定时器 ID
     */
    public static function del(int $timerId): void
    {
        match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_TIMER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_TIMER, 'del', $timerId),
            Runtime::isWorkerman() => WorkermanTimer::del($timerId),
            self::isCliWithPcntl() => PcntlTimer::del($timerId),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 删除所有定时器
     */
    public static function delAll(): void
    {
        match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_TIMER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_TIMER, 'delAll'),
            Runtime::isWorkerman() => WorkermanTimer::delAll(),
            self::isCliWithPcntl() => PcntlTimer::delAll(),
            default => throw new UnsupportedRuntime(),
        };
    }

    private static function isCliWithPcntl(): bool
    {
        return Runtime::isCli() && function_exists('pcntl_alarm');
    }
}
