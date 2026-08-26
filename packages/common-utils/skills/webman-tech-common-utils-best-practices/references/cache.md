# 缓存使用指南

## ArrayCache（单次请求内缓存）

适合在单个 HTTP 请求生命周期内复用数据：

```php
use WebmanTech\CommonUtils\Cache\ArrayCache;

$cache = new ArrayCache(defaultTtl: 3600, maxTtl: 86400);
$cache->set('user:123', $userData);
$user = $cache->get('user:123');
```

**特点**：
- 内存缓存，请求结束后自动销毁
- 适合避免同一请求内的重复查询
- TTL 仅用于防止内存泄漏（maxTtl）

## NullCache（禁用缓存）

用于测试或需要关闭缓存的场景：

```php
use WebmanTech\CommonUtils\Cache\NullCache;
use Psr\SimpleCache\CacheInterface;

class MyService
{
    public function __construct(
        private CacheInterface $cache = new NullCache()
    ) {}

    public function getData(string $key): mixed
    {
        return $this->cache->get($key) ?? $this->expensiveOperation($key);
    }
}
```

**适用场景**：
- 单元测试中隔离缓存依赖
- 开发环境临时禁用缓存
- 实现 PSR-16 CacheInterface 的默认值
