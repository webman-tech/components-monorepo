<?php

use Monolog\Level;
use Monolog\LogRecord;
use WebmanTech\Logger\Middleware\RequestTraceMiddleware;
use WebmanTech\Logger\Processors\RequestIpProcessor;
use WebmanTech\Logger\Processors\RequestRouteProcessor;
use WebmanTech\Logger\Processors\RequestTraceProcessor;

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

    $request = request_create_one();
    $raw = request_get_raw($request);

    // 初始无值检查，无 request 时
    $logRecord = get_log_record();
    expect($logRecord->extra['route'] ?? null)->toBeNull();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['route'])->toBe($raw->getMethod() . ':' . $raw->getPath());

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

test('RequestIpProcessor', function () {
    $processor = new RequestIpProcessor();

    $request = request_create_one();
    request_get_raw($request)->setData('userIp', '8.8.8.8');

    $logRecord = get_log_record();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['ip'])->toBe('8.8.8.8');

    $logRecord->extra['ip'] = 'preset';
    $processor->__invoke($logRecord);
    expect($logRecord->extra['ip'])->toBe('preset');
});

test('RequestTraceProcessor uses custom data and header fallback', function () {
    $processor = new RequestTraceProcessor();

    $request = request_create_one();
    request_get_raw($request)->withCustomData([
        RequestTraceMiddleware::KEY_TRACE_ID => 'trace-custom',
    ]);

    $logRecord = get_log_record();
    $processor->__invoke($logRecord);
    expect($logRecord->extra['traceId'])->toBe('trace-custom');

    $request2 = request_create_one();
    request_get_raw($request2)->withCustomData([
        RequestTraceMiddleware::KEY_TRACE_ID => null,
    ]);
    request_get_raw($request2)->setHeader('X-Trace-Id', 'trace-header');

    $logRecord2 = get_log_record();
    $processor->__invoke($logRecord2);
    expect($logRecord2->extra['traceId'])->toBe('trace-header');
});
