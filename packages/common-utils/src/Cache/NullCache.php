<?php

declare(strict_types=1);

namespace WebmanTech\CommonUtils\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * 空缓存（Null Object 模式）.
 *
 * 所有操作都是空操作，用于禁用缓存或作为默认值。
 *
 * 为兼容 psr/simple-cache 1.0/2.0/3.0，接口方法参数不声明类型，返回值类型与 3.0 接口保持一致，
 * 具体说明详见 ArrayCache 类注释。
 *
 * @see https://en.wikipedia.org/wiki/Null_object_pattern
 */
final class NullCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        $this->verifyKey($key);
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->verifyKey($key);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
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
    public function getMultiple($keys, $default = null): iterable
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
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
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
