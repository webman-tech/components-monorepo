<?php

namespace WebmanTech\CommonUtils\Testing;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use WebmanTech\CommonUtils\Json;

final class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    private static array $instances = [];

    public static function channel(?string $name = null): self
    {
        $name = $name ?? 'default';
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self();
        }
        return self::$instances[$name];
    }

    public static function getLogs(?string $channelName = null): array
    {
        return self::channel($channelName)->getAll();
    }

    public static function reset(?string $channelName = null): void
    {
        self::channel($channelName)->flush();
    }

    public static function clear(): void
    {
        self::$instances = [];
    }

    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => is_string($level) ? strtoupper($level) : $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function flush(): void
    {
        $this->logs = [];
    }

    /**
     * @return array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    public function getAll(bool $flush = true): array
    {
        $all = $this->logs;

        if ($flush) {
            $this->flush();
        }

        return $all;
    }

    public function getAllString(bool $flush = true): string
    {
        $str = implode("\n", array_map(function ($log) {
            return sprintf('[%s] %s %s', $log['level'], $log['message'], Json::encode($log['context']));
        }, $this->getAll(flush: false)));

        if ($flush) {
            $this->flush();
        }

        return $str;
    }
}
