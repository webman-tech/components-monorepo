<?php

declare(strict_types=1);

namespace WebmanTech\CommonUtils\Cache;

use DateInterval;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Clock\ClockAwareTrait;

/**
 * 内存数组缓存.
 *
 * 特性：
 * - 符合 PSR-16 标准
 * - 支持 TTL（过期时间）
 * - 支持默认 TTL 和最大 TTL 限制
 * - 支持最大缓存数量（LRU 淘汰，淘汰时顺带清理过期项）
 * - 支持自动/手动 GC 清理过期缓存
 * - 支持时钟注入（PSR-20），便于测试
 *
 * 注意：缓存数据存储在实例中，需要自行管理实例的生命周期。
 */
final class ArrayCache implements CacheInterface
{
    use ClockAwareTrait;

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * 过期时间戳，PHP_INT_MAX 表示永不过期.
     *
     * @var array<string, int>
     */
    private array $expiries = [];

    /**
     * @param int|null $defaultTtl 默认 TTL（秒），null 表示永不过期
     * @param int|null $maxTtl 最大 TTL（秒），超过此值的 TTL 会被限制，null 表示不限制
     * @param int|null $maxItems 最大缓存数量，超过时使用 LRU 淘汰，null 表示不限制
     * @param float $gcProbability 每次写入自动触发 GC 的概率，范围 0.0~1.0，0 表示不自动 GC（设置了 maxItems 时淘汰流程自带 GC）
     * @param ClockInterface|null $clock PSR-20 时钟接口，null 时使用系统时钟
     */
    public function __construct(
        private readonly ?int  $defaultTtl = null,
        private readonly ?int  $maxTtl = null,
        private readonly ?int  $maxItems = null,
        private readonly float $gcProbability = 0.5,
        ?ClockInterface        $clock = null,
    )
    {
        if ($clock !== null) {
            $this->setClock($clock);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->verifyKey($key);

        if (!$this->hasInternal($key)) {
            return $default;
        }

        return $this->values[$key];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->verifyKey($key);

        $expiry = $this->calculateExpiry($ttl);

        // 如果 TTL 已过期，直接删除而不存入
        if ($this->getCurrentTimestamp() >= $expiry) {
            $this->removeItem($key);
            return true;
        }

        // 如果已存在，先删除；否则检查是否需要淘汰
        if (isset($this->expiries[$key])) {
            $this->removeItem($key);
        } else {
            $this->evictIfNecessary();
        }

        $this->storeItem($key, $value, $expiry);

        $this->autoGc();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->verifyKey($key);
        $this->removeItem($key);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->values = [];
        $this->expiries = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string)$key, $value, $ttl);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $this->verifyKey($key);
        return $this->hasInternal($key);
    }

    /**
     * 清理所有过期的缓存.
     *
     * @return int 清理的缓存数量
     */
    public function gc(): int
    {
        $count = 0;
        $now = $this->getCurrentTimestamp();
        foreach ($this->expiries as $key => $expiry) {
            if ($now > $expiry) {
                $this->removeItem($key);
                $count++;
            }
        }
        return $count;
    }

    /**
     * 获取当前缓存数量.
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * 获取所有缓存的键.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->values);
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

    /**
     * 获取当前时间戳.
     */
    private function getCurrentTimestamp(): int
    {
        return $this->now()->getTimestamp();
    }

    /**
     * 内部 has 检查（跳过键验证）.
     */
    private function hasInternal(string $key): bool
    {
        if (!isset($this->expiries[$key])) {
            return false;
        }

        if ($this->getCurrentTimestamp() > $this->expiries[$key]) {
            $this->removeItem($key);
            return false;
        }

        $this->touchKey($key);

        return true;
    }

    /**
     * LRU：将 key 移到数组末尾（标记为最近使用）.
     */
    private function touchKey(string $key): void
    {
        if ($this->maxItems === null) {
            return;
        }

        $value = $this->values[$key];
        $expiry = $this->expiries[$key];
        $this->removeItem($key);
        $this->storeItem($key, $value, $expiry);
    }

    /**
     * 存储缓存项.
     */
    private function storeItem(string $key, mixed $value, int $expiry): void
    {
        $this->values[$key] = $value;
        $this->expiries[$key] = $expiry;
    }

    /**
     * 移除缓存项.
     */
    private function removeItem(string $key): void
    {
        unset($this->values[$key], $this->expiries[$key]);
    }

    /**
     * 计算过期时间戳.
     */
    private function calculateExpiry(DateInterval|int|null $ttl): int
    {
        $resolvedTtl = $ttl ?? $this->defaultTtl;

        if ($resolvedTtl === null) {
            return PHP_INT_MAX;
        }

        // 转换 DateInterval 为秒数
        if ($resolvedTtl instanceof DateInterval) {
            $now = $this->now();
            $seconds = $now->add($resolvedTtl)->getTimestamp() - $now->getTimestamp();
        } else {
            $seconds = $resolvedTtl;
        }

        // 应用最大 TTL 限制
        if ($this->maxTtl !== null && $seconds > $this->maxTtl) {
            $seconds = $this->maxTtl;
        }

        return $this->getCurrentTimestamp() + $seconds;
    }

    /**
     * 自动 GC：每次写入有 gcProbability 的概率触发.
     */
    private function autoGc(): void
    {
        if ($this->gcProbability <= 0.0) {
            return;
        }

        if ($this->gcProbability >= 1.0 || random_int(1, 1000000) <= (int)($this->gcProbability * 1000000)) {
            $this->gc();
        }
    }

    /**
     * 如果缓存数量达到上限，从数组头部遍历，淘汰过期项和最久未使用项.
     */
    private function evictIfNecessary(): void
    {
        if ($this->maxItems === null || count($this->values) < $this->maxItems) {
            return;
        }

        $now = $this->getCurrentTimestamp();

        // 从头部遍历（最久未使用的在前面），顺带清理过期项
        foreach ($this->values as $k => $v) {
            if ($this->expiries[$k] > $now && count($this->values) < $this->maxItems) {
                break;
            }

            $this->removeItem($k);
        }
    }
}
