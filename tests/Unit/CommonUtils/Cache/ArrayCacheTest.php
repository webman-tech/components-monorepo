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
