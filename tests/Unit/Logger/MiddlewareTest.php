<?php

use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Testing\TestLogger;
use WebmanTech\Logger\Message\HttpRequestMessage;
use WebmanTech\Logger\Middleware\HttpRequestLogMiddleware;
use WebmanTech\Logger\Middleware\RequestTraceMiddleware;

test('RequestTraceMiddleware', function () {
    $request = request_create_one();

    expect($request->getCustomData(RequestTraceMiddleware::KEY_TRACE_ID))->toBeNull();

    $middleware = new RequestTraceMiddleware();
    $middleware->process($request, fn() => Response::make());

    expect($request->getCustomData(RequestTraceMiddleware::KEY_TRACE_ID))->not->toBeNull();
});

test('HttpRequestLogMiddleware logs successful request', function () {
    setup_http_request_message([
        'logMinTimeMS' => 0,
        'logRequestBodyLimitSize' => 100,
    ]);
    TestLogger::channel('httpRequest')->flush();

    $request = request_create_one();
    $raw = request_get_raw($request);
    $raw->setData('method', 'POST')
        ->setData('path', '/middleware')
        ->setGet('foo', 'bar')
        ->setHeader('content-type', 'application/json')
        ->setHeader('content-length', '30')
        ->setData('rawBody', '{"password":"123456","name":"neo"}');

    $middleware = new HttpRequestLogMiddleware();
    $middleware->process($request, function () {
        return Response::make()->withStatus(204);
    });

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1);

    $log = $logs[0];
    expect($log['message'])->toBe('POST:/middleware')
        ->and($log['context']['response_status'])->toBe(204)
        ->and($log['context']['query'])->toBe('foo=bar')
        ->and($log['context']['body'])->toBe('{"password":"***","name":"neo"}');
});

test('HttpRequestLogMiddleware logs exception details', function () {
    setup_http_request_message([
        'logMinTimeMS' => 0,
    ]);
    TestLogger::channel('httpRequest')->flush();

    $request = request_create_one();
    request_get_raw($request)->setData('path', '/middleware-error');

    $middleware = new HttpRequestLogMiddleware();
    try {
        $middleware->process($request, function () {
            throw new \RuntimeException('middleware boom');
        });
    } catch (Throwable) {
        // 忽略
    }

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1);

    $log = $logs[0];
    expect($log['level'])->toBe('WARNING')
        ->and($log['context']['response_exception'])->toBe('middleware boom');
});

function setup_http_request_message(array $config): void
{
    $message = new HttpRequestMessage($config);
    $message->setClock(new MockClock('2024-01-01 00:00:00'));

    $reflection = new \ReflectionClass(HttpRequestLogMiddleware::class);
    $property = $reflection->getProperty('message');
    $property->setAccessible(true);
    $property->setValue(null, $message);
}
