<?php

declare(strict_types=1);

use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\Cache\ArrayCache;

beforeEach(function () {
    $this->clock = new MockClock('@1000');
    $this->cache = new ArrayCache(clock: $this->clock);
});

describe('ArrayCache - Basic Operations', function () {
    it('can set and get value', function () {
        $this->cache->set('key1', 'value1');

        expect($this->cache->get('key1'))->toBe('value1')
            ->and($this->cache->has('key1'))->toBeTrue();
    });

    it('returns default for non-existent key', function () {
        expect($this->cache->get('not_exist'))->toBeNull()
            ->and($this->cache->get('not_exist', 'default'))->toBe('default');
    });

    it('can delete key', function () {
        $this->cache->set('key1', 'value1');

        expect($this->cache->has('key1'))->toBeTrue();

        $this->cache->delete('key1');

        expect($this->cache->has('key1'))->toBeFalse();
    });

    it('can clear all keys', function () {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        expect($this->cache->count())->toBe(2);

        $this->cache->clear();

        expect($this->cache->count())->toBe(0);
    });

    it('can overwrite existing key', function () {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key1', 'value2');

        expect($this->cache->get('key1'))->toBe('value2')
            ->and($this->cache->count())->toBe(1);
    });
});

describe('ArrayCache - Multiple Operations', function () {
    it('can get multiple keys', function () {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $result = $this->cache->getMultiple(['key1', 'key2', 'key3'], 'default');

        expect((array) $result)->toBe([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default',
        ]);
    });

    it('can set multiple keys', function () {
        $this->cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        expect($this->cache->get('key1'))->toBe('value1')
            ->and($this->cache->get('key2'))->toBe('value2');
    });

    it('can delete multiple keys', function () {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->cache->deleteMultiple(['key1', 'key2']);

        expect($this->cache->has('key1'))->toBeFalse()
            ->and($this->cache->has('key2'))->toBeFalse()
            ->and($this->cache->has('key3'))->toBeTrue();
    });
});

describe('ArrayCache - TTL', function () {
    it('expires after TTL', function () {
        $this->cache->set('key1', 'value1', 10);

        expect($this->cache->has('key1'))->toBeTrue();

        // 前进 10 秒，还未过期
        $this->clock->sleep(10);
        expect($this->cache->has('key1'))->toBeTrue();

        // 再前进 1 秒，已过期
        $this->clock->sleep(1);
        expect($this->cache->has('key1'))->toBeFalse()
            ->and($this->cache->get('key1'))->toBeNull();
    });

    it('supports DateInterval TTL', function () {
        $interval = new DateInterval('PT10S');
        $this->cache->set('key1', 'value1', $interval);

        expect($this->cache->has('key1'))->toBeTrue();

        $this->clock->sleep(11);

        expect($this->cache->has('key1'))->toBeFalse();
    });

    it('uses default TTL when not specified', function () {
        $cache = new ArrayCache(defaultTtl: 10, clock: $this->clock);

        $cache->set('key1', 'value1');

        expect($cache->has('key1'))->toBeTrue();

        $this->clock->sleep(10);
        expect($cache->has('key1'))->toBeTrue();

        $this->clock->sleep(1);
        expect($cache->has('key1'))->toBeFalse();
    });

    it('limits TTL to max TTL', function () {
        $cache = new ArrayCache(maxTtl: 5, clock: $this->clock);

        $cache->set('key1', 'value1', 100); // 尝试设置 100 秒

        expect($cache->has('key1'))->toBeTrue();

        // 前进 5 秒，还未过期
        $this->clock->sleep(5);
        expect($cache->has('key1'))->toBeTrue();

        // 再前进 1 秒，已过期（被限制为 5 秒）
        $this->clock->sleep(1);
        expect($cache->has('key1'))->toBeFalse();
    });

    it('does not store value when TTL is already expired', function () {
        // 设置 TTL 为 0 秒，立即过期
        $this->cache->set('key1', 'value1', 0);
        expect($this->cache->has('key1'))->toBeFalse()
            ->and($this->cache->count())->toBe(0);

        // 负数 TTL 也不应存入
        $this->cache->set('key2', 'value2', -5);
        expect($this->cache->has('key2'))->toBeFalse()
            ->and($this->cache->count())->toBe(0);
    });

    it('removes existing key when set with already expired TTL', function () {
        $this->cache->set('key1', 'value1', 60);
        expect($this->cache->has('key1'))->toBeTrue();

        // 用已过期的 TTL 重新 set，应该删除已有的 key
        $this->cache->set('key1', 'value2', 0);
        expect($this->cache->has('key1'))->toBeFalse()
            ->and($this->cache->count())->toBe(0);
    });
});

describe('ArrayCache - LRU Eviction', function () {
    it('evicts least recently used items when max items reached', function () {
        $cache = new ArrayCache(maxItems: 3, clock: $this->clock);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        expect($cache->count())->toBe(3);

        // 访问 key1，使其成为最近使用
        $cache->get('key1');

        // 添加新 key，触发 LRU 淘汰
        $cache->set('key4', 'value4');

        // key1 被访问过，key2 应该被淘汰（最久未使用）
        expect($cache->has('key1'))->toBeTrue()
            ->and($cache->has('key2'))->toBeFalse()
            ->and($cache->has('key3'))->toBeTrue()
            ->and($cache->has('key4'))->toBeTrue();
    });

    it('has() also updates LRU order', function () {
        $cache = new ArrayCache(maxItems: 3, clock: $this->clock);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        // 用 has() 访问 key1，应该更新 LRU 顺序
        $cache->has('key1');

        // 添加新 key，触发淘汰，key2 应该被淘汰（最久未使用）
        $cache->set('key4', 'value4');

        expect($cache->has('key1'))->toBeTrue()
            ->and($cache->has('key2'))->toBeFalse()
            ->and($cache->has('key3'))->toBeTrue()
            ->and($cache->has('key4'))->toBeTrue();
    });

    it('evicts expired items first during LRU eviction', function () {
        $cache = new ArrayCache(maxItems: 3, gcProbability: 0.0, clock: $this->clock);

        $cache->set('key1', 'value1', 5);
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        // key1 过期
        $this->clock->sleep(6);

        // 添加新 key，淘汰遍历时 key1 已过期会被优先清理
        $cache->set('key4', 'value4');

        expect($cache->has('key1'))->toBeFalse()
            ->and($cache->has('key2'))->toBeTrue()
            ->and($cache->has('key3'))->toBeTrue()
            ->and($cache->has('key4'))->toBeTrue();
    });
});

describe('ArrayCache - GC', function () {
    it('garbage collects expired items', function () {
        $this->cache->set('key1', 'value1', 10);
        $this->cache->set('key2', 'value2', 10);
        $this->cache->set('key3', 'value3', null); // 不过期

        $count = $this->cache->gc();
        expect($count)->toBe(0); // 未过期，清理 0 个

        // 前进 11 秒
        $this->clock->sleep(11);

        $count = $this->cache->gc();

        expect($count)->toBe(2) // key1 和 key2 被清理
            ->and($this->cache->has('key1'))->toBeFalse()
            ->and($this->cache->has('key2'))->toBeFalse()
            ->and($this->cache->has('key3'))->toBeTrue();
    });

    it('auto gc triggers on set with 100% probability', function () {
        $cache = new ArrayCache(gcProbability: 1.0, clock: $this->clock);

        $cache->set('key1', 'value1', 10);
        $cache->set('key2', 'value2', 10);

        // 前进 11 秒，全部过期
        $this->clock->sleep(11);

        // 过期的 key 还在（未被访问，未触发 GC）
        expect($cache->count())->toBe(2);

        // 写入新 key，100% 触发 GC，过期 key 被清理
        $cache->set('key3', 'value3', 10);
        expect($cache->count())->toBe(1)
            ->and($cache->has('key3'))->toBeTrue();
    });

    it('does not auto gc when gcProbability is 0', function () {
        $cache = new ArrayCache(gcProbability: 0.0, clock: $this->clock);

        $cache->set('key1', 'value1', 10);
        $this->clock->sleep(11);

        // 写入多次也不会自动 GC
        for ($i = 0; $i < 200; $i++) {
            $cache->set("new{$i}", "v{$i}", 10);
        }

        // key1 过期但仍在 cache 中（count 包含过期 key）
        expect($cache->count())->toBe(201);
    });

    it('auto gc respects probability distribution', function () {
        $gcCount = 0;
        $trials = 1000;

        for ($i = 0; $i < $trials; $i++) {
            $cache = new ArrayCache(gcProbability: 0.5, clock: $this->clock);
            $cache->set('expired', 'value', 1);
            $this->clock->sleep(2);
            // set 触发 autoGc，概率 50% 清理过期 key
            $cache->set('trigger', 'value');
            if ($cache->count() === 1) {
                $gcCount++;
            }
        }

        // p=0.5, n=1000, 期望 ~500 次，允许较宽的统计波动范围
        expect($gcCount)->toBeGreaterThan(350)
            ->and($gcCount)->toBeLessThan(650);
    });
});

describe('ArrayCache - Keys and Count', function () {
    it('returns all keys', function () {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        expect($this->cache->keys())->toBe(['key1', 'key2']);
    });

    it('returns correct count', function () {
        expect($this->cache->count())->toBe(0);

        $this->cache->set('key1', 'value1');
        expect($this->cache->count())->toBe(1);

        $this->cache->set('key2', 'value2');
        expect($this->cache->count())->toBe(2);

        $this->cache->delete('key1');
        expect($this->cache->count())->toBe(1);
    });
});

describe('ArrayCache - Key Validation', function () {
    it('throws exception for empty key', function () {
        expect(fn () => $this->cache->get(''))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('allows special characters in key', function (string $key) {
        $this->cache->set($key, 'value');
        expect($this->cache->get($key))->toBe('value');
    })->with([
        'key/with/slashes',
        'key{with}braces',
        'key(with)parens',
        'key@with@at',
        'key:with:colon',
        'key\\with\\backslash',
    ]);
});

describe('ArrayCache - Instance Isolation', function () {
    it('does not share cache between different instances', function () {
        $cache1 = new ArrayCache(clock: $this->clock);
        $cache2 = new ArrayCache(clock: $this->clock);

        $cache1->set('shared', 'value');

        expect($cache2->get('shared'))->toBeNull();
    });
});

describe('ArrayCache - Complex Values', function () {
    it('can store complex nested values', function () {
        $complexValue = [
            'nested' => [
                'array' => [1, 2, 3],
                'object' => (object) ['foo' => 'bar'],
            ],
            'null' => null,
            'bool' => true,
        ];

        $this->cache->set('complex', $complexValue);

        expect($this->cache->get('complex'))->toBe($complexValue);
    });
});

describe('ArrayCache - Return Values', function () {
    it('set returns true', function () {
        expect($this->cache->set('key1', 'value1'))->toBeTrue()
            ->and($this->cache->set('key1', 'value2', 10))->toBeTrue();
    });

    it('delete returns true', function () {
        $this->cache->set('key1', 'value1');

        expect($this->cache->delete('key1'))->toBeTrue()
            ->and($this->cache->delete('not_exist'))->toBeTrue();
    });
});
