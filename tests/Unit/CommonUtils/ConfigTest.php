<?php

use WebmanTech\CommonUtils\Config;

test('get', function () {
    expect(Config::get('custom.config_test.exist'))->toBe('abc')
        ->and(Config::get('custom.config_test.exist_number'))->toBe(123)
        ->and(Config::get('custom.config_test.exist_array'))->toBe(['1', '2'])
        ->and(Config::get('custom.config_test.exist_bool'))->toBeFalse()
        ->and(Config::get('custom.config_test.exist_fn'))->toBeCallable();
});

test('get default', function () {
    expect(Config::get('custom.config_test.not_exist'))->toBeNull()
        ->and(Config::get('not_exist', 'default'))->toBe('default')
        ->and(Config::get('not_exist', function () {
            return 'default';
        }))->toBe('default')
        ->and(Config::get('custom.config_test.exist_fn', function () {
            return fn() => 'default';
        }))->toBeCallable();
});

test('requireFromConfigPath', function () {
    expect(Config::requireFromConfigPath('custom'))->toBeArray()
        ->and(Config::requireFromConfigPath('custom.php'))->toBeArray();
});
