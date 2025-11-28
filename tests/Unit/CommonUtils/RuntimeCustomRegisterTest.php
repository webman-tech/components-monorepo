<?php

use WebmanTech\CommonUtils\RuntimeCustomRegister;

test('runtime custom register stores and clears closures', function () {
    $key = '__unit_runtime_key';
    RuntimeCustomRegister::register($key, function (string $value): string {
        return strtoupper($value);
    });

    expect(RuntimeCustomRegister::isRegistered($key))->toBeTrue()
        ->and(RuntimeCustomRegister::getRegistered($key))->toBeInstanceOf(\Closure::class)
        ->and(RuntimeCustomRegister::call($key, 'demo'))->toBe('DEMO');

    RuntimeCustomRegister::register($key, null);
    expect(RuntimeCustomRegister::isRegistered($key))->toBeFalse()
        ->and(RuntimeCustomRegister::getRegistered($key))->toBeNull();
});

test('runtime custom register throws when calling missing key', function () {
    expect(fn() => RuntimeCustomRegister::call('__unit_missing_key'))
        ->toThrow(\InvalidArgumentException::class);
});
