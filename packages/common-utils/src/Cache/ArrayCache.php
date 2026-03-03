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
 * - 支持最大缓存数量（LRU 淘汰）
 * - 支持手动 GC 清理过期缓存
 * - 支持时钟注入（PSR-20），便于测试
 *
 * 注意：缓存数据存储在实例中，需要自行管理实例的生命周期。
 */
final class ArrayCache implements CacheInterface
{
    use ClockAwareTrait;

    /**
     * @var array<string, array{value: mixed, expire: int|null, accessed: int}>
     */
    private array $cache = [];

    private int $accessCounter = 0;

    /**
     * @param int|null $defaultTtl 默认 TTL（秒），null 表示永不过期
     * @param int|null $maxTtl 最大 TTL（秒），超过此值的 TTL 会被限制，null 表示不限制
     * @param int|null $maxItems 最大缓存数量，超过时使用 LRU 淘汰，null 表示不限制
     * @param ClockInterface|null $clock PSR-20 时钟接口，null 时使用系统时钟
     */
    public function __construct(
        private readonly ?int $defaultTtl = null,
        private readonly ?int $maxTtl = null,
        private readonly ?int $maxItems = null,
        ?ClockInterface       $clock = null,
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

        // 更新访问时间（用于 LRU）
        $this->cache[$key]['accessed'] = ++$this->accessCounter;

        // @phpstan-ignore-next-line
        return $this->cache[$key]['value'];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->verifyKey($key);

        $expire = $this->calculateExpire($ttl);

        // 如果已存在，先删除；否则检查是否需要淘汰
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        } else {
            $this->evictIfNecessary();
        }

        $this->cache[$key] = [
            'value' => $value,
            'expire' => $expire,
            'accessed' => ++$this->accessCounter,
        ];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->verifyKey($key);
        unset($this->cache[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->cache = [];
        $this->accessCounter = 0;
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
        foreach ($this->cache as $key => $item) {
            if ($item['expire'] !== null && $now > $item['expire']) {
                unset($this->cache[$key]);
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
        return count($this->cache);
    }

    /**
     * 获取所有缓存的键.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->cache);
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
        if (!isset($this->cache[$key])) {
            return false;
        }

        // 检查是否过期
        $expire = $this->cache[$key]['expire'];
        if ($expire !== null && $this->getCurrentTimestamp() > $expire) {
            unset($this->cache[$key]);
            return false;
        }

        return true;
    }

    /**
     * 计算过期时间戳.
     */
    private function calculateExpire(DateInterval|int|null $ttl): ?int
    {
        $resolvedTtl = $ttl ?? $this->defaultTtl;

        if ($resolvedTtl === null) {
            return null;
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
     * 如果缓存数量达到上限，使用 LRU 淘汰.
     */
    private function evictIfNecessary(): void
    {
        if ($this->maxItems === null || count($this->cache) < $this->maxItems) {
            return;
        }

        // 按 accessed 升序排序，找到最久未访问的键
        $keysByAccessed = array_map(function ($item) {
            return $item['accessed'];
        }, $this->cache);
        asort($keysByAccessed); // 按 accessed 升序排序

        // 淘汰 10% 或至少 1 个（取排序后的前 N 个）
        $evictCount = max(1, (int)($this->maxItems * 0.1));
        $keysToEvict = array_slice(array_keys($keysByAccessed), 0, $evictCount);

        foreach ($keysToEvict as $key) {
            unset($this->cache[$key]);
        }
    }
}
