<?php

declare(strict_types=1);

namespace WebmanTech\CommonUtils\Timer;

use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;
use Throwable;

/**
 * 基于 pcntl_alarm 的 CLI 定时器实现
 * 精度为秒级，适用于 watchdog 等场景
 */
final class PcntlTimer
{
    /** @var array<int, array{runTime: int, func: callable, args: array, persistent: bool, interval: int}> 按 timerId 索引 */
    private static array $timers = [];

    private static int $nextId = 1;

    private static bool $initialized = false;

    private static ?ClockInterface $clock = null;

    /**
     * 设置时钟（测试用，可注入可控时钟）
     */
    public static function setClock(?ClockInterface $clock): void
    {
        self::$clock = $clock;
    }

    /**
     * 方法分发（供 RuntimeCustomRegister 注册调用）
     */
    public static function call(string $method, mixed ...$args): mixed
    {
        return match ($method) {
            'add' => self::add($args[0], $args[1], $args[2], $args[3]),
            'delay' => self::add($args[0], $args[1], $args[2], false),
            'repeat' => self::add($args[0], $args[1], $args[2], true),
            'del' => self::del($args[0]),
            'delAll' => self::delAll(),
            default => throw new \InvalidArgumentException("Unknown Timer method: $method"),
        };
    }

    public static function add(float $interval, callable $func, array $args, bool $persistent): int
    {
        self::init();

        $id = self::$nextId++;
        $seconds = max(1, (int) ceil($interval));

        self::$timers[$id] = [
            'runTime' => self::now() + $seconds,
            'func' => $func,
            'args' => $args,
            'persistent' => $persistent,
            'interval' => $seconds,
        ];

        self::reschedule();

        return $id;
    }

    public static function del(int $timerId): void
    {
        unset(self::$timers[$timerId]);
        self::reschedule();
    }

    public static function delAll(): void
    {
        self::$timers = [];
        if (!isset(self::$clock)) {
            pcntl_alarm(0);
        }
    }

    /**
     * 检查并执行到期的定时器
     */
    public static function tick(): void
    {
        if (empty(self::$timers)) {
            if (!isset(self::$clock)) {
                pcntl_alarm(0);
            }
            return;
        }

        $now = self::now();
        $toReschedule = [];

        foreach (self::$timers as $id => $timer) {
            if ($now >= $timer['runTime']) {
                try {
                    ($timer['func'])(...$timer['args']);
                } catch (Throwable) {
                    // 静默处理，避免中断其他定时器
                }

                unset(self::$timers[$id]);

                if ($timer['persistent']) {
                    $toReschedule[$id] = $timer;
                }
            }
        }

        // 重新调度持久化定时器
        foreach ($toReschedule as $id => $timer) {
            $timer['runTime'] = self::now() + $timer['interval'];
            self::$timers[$id] = $timer;
        }

        self::reschedule();
    }

    /**
     * 重置内部状态（测试用）
     */
    public static function reset(): void
    {
        self::delAll();
        self::$nextId = 1;
        self::$initialized = false;
        self::$clock = null;
    }

    private static function now(): int
    {
        return self::getClock()->now()->getTimestamp();
    }

    private static function getClock(): ClockInterface
    {
        return self::$clock ??= new Clock();
    }

    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        if (!isset(self::$clock)) {
            pcntl_async_signals(true);
            pcntl_signal(\SIGALRM, self::handleSignal(...));
        }
        self::$initialized = true;
    }

    private static function handleSignal(int $signal): void
    {
        if ($signal !== \SIGALRM) {
            return;
        }
        self::tick();
    }

    private static function reschedule(): void
    {
        if (empty(self::$timers)) {
            if (!isset(self::$clock)) {
                pcntl_alarm(0);
            }
            return;
        }

        $minRunTime = \PHP_INT_MAX;
        foreach (self::$timers as $timer) {
            $minRunTime = min($minRunTime, $timer['runTime']);
        }

        if (!isset(self::$clock)) {
            $seconds = max(1, $minRunTime - self::now());
            pcntl_alarm($seconds);
        }
    }
}
