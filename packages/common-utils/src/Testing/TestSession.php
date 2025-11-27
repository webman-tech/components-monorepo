<?php

namespace WebmanTech\CommonUtils\Testing;

/**
 * 简易 Session，用于测试环境
 */
final class TestSession
{
    private array $data = [];

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function clear(): void
    {
        self::$instance = null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }
}
