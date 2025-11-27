<?php

use Monolog\Level;
use Monolog\LogRecord;
use WebmanTech\Logger\Processors\RequestRouteProcessor;

function get_log_record(): LogRecord
{
    return new LogRecord(
        new DateTimeImmutable(),
        'channel',
        Level::Info,
        'message',
    );
}

test('RequestRouteProcessor', function () {
    $processor = new RequestRouteProcessor();

    // 初始无值检查，无 request 时
    $logRecord = get_log_record();
    expect($logRecord->extra['route'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe('GET:/');

    // 初始无值检查，有 request 时
    $request = request_create_one();
    $logRecord = get_log_record();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe($request->getMethod() . ':' . $request->getPath());

    // 初始有值检查
    $logRecord = get_log_record();
    $logRecord->extra['route'] = '/route';
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe('/route');
});
