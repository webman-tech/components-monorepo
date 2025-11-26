<?php

namespace WebmanTech\CommonUtils\Testing;

/**
 * 简易 Session，用于测试环境
 */
final class TestSession
{
    private array $data = [];

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
