<?php

declare(strict_types=1);

namespace WebmanTech\CommonUtils\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * 空缓存（Null Object 模式）.
 *
 * 所有操作都是空操作，用于禁用缓存或作为默认值。
 *
 * @see https://en.wikipedia.org/wiki/Null_object_pattern
 */
final class NullCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->verifyKey($key);
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->verifyKey($key);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->verifyKey($key);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $this->verifyKey($key);
        return false;
    }

    /**
     * 验证缓存键.
     */
    private function verifyKey(string $key): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Cache key cannot be empty');
        }
    }
}
