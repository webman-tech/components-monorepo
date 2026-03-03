<?php

declare(strict_types=1);

use WebmanTech\CommonUtils\Cache\NullCache;

describe('NullCache', function () {
    beforeEach(function () {
        $this->cache = new NullCache();
    });

    describe('Basic Operations', function () {
        it('get always returns default value', function () {
            expect($this->cache->get('any_key'))->toBeNull()
                ->and($this->cache->get('any_key', 'default'))->toBe('default')
                ->and($this->cache->get('any_key', ['complex' => 'value']))->toBe(['complex' => 'value']);
        });

        it('set does nothing and returns true', function () {
            expect($this->cache->set('key', 'value'))->toBeTrue()
                ->and($this->cache->get('key'))->toBeNull();
        });

        it('delete returns true', function () {
            expect($this->cache->delete('key'))->toBeTrue();
        });

        it('clear returns true', function () {
            expect($this->cache->clear())->toBeTrue();
        });

        it('has always returns false', function () {
            $this->cache->set('key', 'value');
            expect($this->cache->has('key'))->toBeFalse()
                ->and($this->cache->has('non_existent'))->toBeFalse();
        });
    });

    describe('Multiple Operations', function () {
        it('getMultiple returns default for all keys', function () {
            $result = $this->cache->getMultiple(['key1', 'key2', 'key3'], 'default');
            expect($result)->toBe(['key1' => 'default', 'key2' => 'default', 'key3' => 'default']);
        });

        it('setMultiple returns true and does nothing', function () {
            expect($this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']))->toBeTrue()
                ->and($this->cache->get('key1'))->toBeNull()
                ->and($this->cache->get('key2'))->toBeNull();
        });

        it('deleteMultiple returns true', function () {
            expect($this->cache->deleteMultiple(['key1', 'key2']))->toBeTrue();
        });
    });

    describe('Key Validation', function () {
        it('throws exception for empty key in get', function () {
            expect(fn () => $this->cache->get(''))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws exception for empty key in set', function () {
            expect(fn () => $this->cache->set('', 'value'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws exception for empty key in delete', function () {
            expect(fn () => $this->cache->delete(''))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws exception for empty key in has', function () {
            expect(fn () => $this->cache->has(''))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('TTL Support', function () {
        it('accepts TTL in set but ignores it', function () {
            expect($this->cache->set('key', 'value', 3600))->toBeTrue()
                ->and($this->cache->set('key', 'value', new DateInterval('PT1H')))->toBeTrue();
        });

        it('accepts TTL in setMultiple but ignores it', function () {
            expect($this->cache->setMultiple(['key' => 'value'], 3600))->toBeTrue();
        });
    });
});
