<?php

use Monolog\Level;
use Monolog\LogRecord;
use WebmanTech\Logger\Middleware\RequestUid;
use WebmanTech\Logger\Processors\CurrentUserProcessor;
use WebmanTech\Logger\Processors\RequestRouteProcessor;
use WebmanTech\Logger\Processors\RequestUidProcessor;

function get_log_record(): LogRecord
{
    return new LogRecord(
        new DateTimeImmutable(),
        'channel',
        Level::Info,
        'message',
    );
}

test('CurrentUserProcessor', function () {
    $processor = new CurrentUserProcessor(function () {
        return 'abc123';
    });

    // 初始无值检查
    $logRecord = get_log_record();
    expect($logRecord->extra['ip'] ?? null)->toBeNull()
        ->and($logRecord->extra['userId'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['ip'])->toBe('0.0.0.0')
        ->and($logRecord->extra['userId'])->toBe('abc123');

    // 初始有值检查
    $logRecord = get_log_record();
    $logRecord->extra['ip'] = '1.2.3.4';
    $logRecord->extra['userId'] = 'xyz123';
    $processor->__invoke($logRecord);
    expect($logRecord->extra['ip'])->toBe('1.2.3.4')
        ->and($logRecord->extra['userId'])->toBe('xyz123');
});

test('RequestRouteProcessor', function () {
    $processor = new RequestRouteProcessor();

    // 初始无值检查，无 request 时
    $logRecord = get_log_record();
    expect($logRecord->extra['route'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe('/');

    // 初始无值检查，有 request 时
    $request = request_create_one();
    $logRecord = get_log_record();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe($request->method() . ':' . $request->path());

    // 初始有值检查
    $logRecord = get_log_record();
    $logRecord->extra['route'] = '/route';
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe('/route');
});

test('RequestUidProcessor', function () {
    $processor = new RequestUidProcessor();

    // 初始无值检查
    $logRecord = get_log_record();
    expect($logRecord->extra['uid'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['uid'])->not->toBeEmpty();

    // 初始有值检查
    $logRecord = get_log_record();
    $logRecord->extra['uid'] = 'uuuid';
    $processor->__invoke($logRecord);
    expect($logRecord->extra['uid'])->toBe('uuuid');

    // 有 request 检查
    $request = request_create_one();
    $request->{RequestUid::REQUEST_UID_KEY} = 'request_uid';
    $logRecord = get_log_record();
    expect($logRecord->extra['uid'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['uid'])->toBe('request_uid');
});
