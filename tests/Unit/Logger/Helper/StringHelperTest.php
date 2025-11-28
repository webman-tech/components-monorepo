<?php

use WebmanTech\Logger\Helper\StringHelper;

test('mask sensitive fields in json payload', function () {
    $content = '{"password":"123","token":"abc","name":"neo"}';
    $masked = StringHelper::maskSensitiveFields($content, ['password', 'token']);

    expect($masked)->toBe('{"password":"***","token":"***","name":"neo"}');
});

test('mask sensitive fields in form payload', function () {
    $content = 'password=123&token=abc&name=neo';
    $masked = StringHelper::maskSensitiveFields($content, ['password', 'token']);

    expect($masked)->toBe('password=***&token=***&name=neo');
});

test('mask sensitive fields in non-structured payload', function () {
    $content = 'raw_password_value';
    $masked = StringHelper::maskSensitiveFields($content, ['password']);

    expect($masked)->toBe('[Contain Sensitive password]');
});

test('mask sensitive fields with custom replacement', function () {
    $content = '{"secret":"abc"}';
    $masked = StringHelper::maskSensitiveFields($content, ['secret'], '[redacted]');

    expect($masked)->toBe('{"secret":"[redacted]"}');
});

test('limit helper handles short and long strings', function () {
    expect(StringHelper::limit('short', 10))->toBe('short');

    $long = 'abcdefghijklmnopqrstuvwxyz';
    expect(StringHelper::limit($long, 5))->toBe('abcde...');
});
