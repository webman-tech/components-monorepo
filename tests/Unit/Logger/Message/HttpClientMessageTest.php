<?php

use Symfony\Component\Clock\MockClock;
use Tests\Fixtures\Logger\FakeHttpClientMessage;
use WebmanTech\CommonUtils\Testing\TestLogger;

beforeEach(function () {
    // HttpClient channel logger 每次测试前重置
    TestLogger::channel('httpClient')->flush();
});

test('simple request log', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 0,
        'logRequestBodyLimitSize' => 20,
        'logResponseBodyLimitSize' => 10,
    ]);

    $options = [
        'json' => ['id' => 1],
        'null_value' => null,
    ];

    $message->markRequestStart('POST', 'https://example.com/api/users?foo=1', $options);
    $clock->sleep(1.234);
    $message->markResponseEnd([
        'status' => 200,
        'body' => str_repeat('*', 50),
    ]);

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(1);

    $log = $logs[0];
    expect($log['level'])->toBe('INFO')
        ->and($log['message'])->toBe('POST:/api/users')
        ->and($log['context']['cost'])->toBe(1234)
        ->and($log['context']['method'])->toBe('POST')
        ->and($log['context']['host'])->toBe('example.com')
        ->and($log['context']['path'])->toBe('/api/users')
        ->and($log['context']['response_status'])->toBe(200);

    expect($log['context']['response_content'])->toBe(str_repeat('*', 10) . '...');

    $requestOptions = $log['context']['request_options'];
    expect($requestOptions)->toHaveKey('json')
        ->and($requestOptions['json'])->toMatchArray(['id' => 1])
        ->and(isset($requestOptions['null_value']))->toBeFalse();
});

test('skip request by config', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 0,
        'skipRequest' => function (array $request) {
            return ($request['options']['_logger']['skip'] ?? false) === true;
        },
    ]);

    $message->appendSkipUrls('/\\/ignore\\//');

    $send = function (string $url, array $loggerConfig = []) use ($message, $clock) {
        $options = [
            '_logger' => $loggerConfig,
        ];
        $message->markRequestStart('GET', $url, $options);
        $clock->sleep(0.5);
        $message->markResponseEnd([
            'status' => 200,
            'body' => 'ok',
        ]);
    };

    $send('https://example.com/ignore/path'); // 通过正则忽略
    $send('https://example.com/normal', ['skip' => true]); // skipRequest 闭包忽略
    $send('https://example.com/normal'); // 记录

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]['message'])->toBe('GET:/normal');
});

test('time and response levels', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 0,
        'warningTimeMS' => 1500,
        'errorTimeMS' => 3000,
    ]);

    $send = function (float $seconds, int $status = 200) use ($message, $clock) {
        $message->markRequestStart('GET', 'https://example.com/check', []);
        $clock->sleep($seconds);
        $message->markResponseEnd([
            'status' => $status,
            'body' => 'ok',
        ]);
    };

    $send(0.5, 200);  // info
    $send(2.0, 200);  // warning(时间)
    $send(4.0, 200);  // error(时间)
    $send(0.5, 404);  // warning(状态码)
    $send(0.5, 503);  // error(状态码)

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(5)
        ->and($logs[0]['level'])->toBe('INFO')
        ->and($logs[1]['level'])->toBe('WARNING')
        ->and($logs[2]['level'])->toBe('ERROR')
        ->and($logs[3]['level'])->toBe('WARNING')
        ->and($logs[4]['level'])->toBe('ERROR');
});

test('switch enable works', function () {
    [$message, $clock] = createHttpClientMessage(['logMinTimeMS' => 0]);

    $message->markRequestStart('GET', 'https://example.com/1', []);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    $message->switchEnable(false);
    $message->markRequestStart('GET', 'https://example.com/2', []);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    $message->switchEnable(true);
    $message->markRequestStart('GET', 'https://example.com/3', []);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(2)
        ->and($logs[0]['message'])->toBe('GET:/1')
        ->and($logs[1]['message'])->toBe('GET:/3');
});

test('per request timing config via _logger', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 1000,
        'warningTimeMS' => 2000,
    ]);

    // 默认 logMinTimeMS 下不会记录
    $message->markRequestStart('GET', 'https://example.com/default', []);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    // 单次请求降低 logMinTimeMS
    $message->markRequestStart('GET', 'https://example.com/custom', [
        '_logger' => [
            'logMinTimeMS' => 0,
        ],
    ]);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    // 单次请求直接 skip
    $message->markRequestStart('GET', 'https://example.com/skip', [
        '_logger' => [
            'logMinTimeMS' => 0,
            'skip' => true,
        ],
    ]);
    $clock->sleep(0.5);
    $message->markResponseEnd(['status' => 200, 'body' => 'ok']);

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]['message'])->toBe('GET:/custom')
        ->and($logs[0]['level'])->toBe('INFO');
});

test('exception info and option normalization', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 0,
        'logRequestBody' => false,
        'logResponseBodyLimitSize' => 5,
        'extraInfo' => function (array $request, mixed $response) {
            return [
                'tenant' => 'demo',
                'response_size' => strlen((string)($response['body'] ?? '')),
            ];
        },
        'logRequestOptionsFn' => function (array $options) {
            $options['trace_id'] = $options['extra']['trace_id'] ?? null;
            return $options;
        },
    ]);

    $options = [
        'json' => ['foo' => 'bar'],
        'extra' => [
            'trace_id' => 'trace-123',
        ],
        '_logger' => [
            'extraInfo' => function () {
                return ['order_id' => 9527];
            },
            'logRequestOptionsFn' => function (array $options) {
                $options['per_request'] = true;
                return $options;
            },
        ],
    ];

    $message->markRequestStart('PUT', 'https://example.com/orders', $options);
    $clock->sleep(0.5);
    $message->markResponseEnd(
        ['status' => 200, 'body' => 'abcdefghi'],
        new RuntimeException('boom')
    );

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(1);

    $context = $logs[0]['context'];
    expect($logs[0]['level'])->toBe('WARNING')
        ->and($context['response_status'])->toBe(200)
        ->and($context['response_content'])->toBe('abcde...')
        ->and($context['response_exception'])->toBe('boom')
        ->and($context['tenant'])->toBe('demo')
        ->and($context['response_size'])->toBe(9)
        ->and($context['order_id'])->toBe(9527);

    $requestOptions = $context['request_options'];
    expect($requestOptions['json'])->toBe('[skip]')
        ->and($requestOptions['extra']['trace_id'])->toBe('trace-123')
        ->and($requestOptions['trace_id'])->toBe('trace-123')
        ->and($requestOptions['per_request'])->toBeTrue();
});

test('log request and response body config', function () {
    [$message, $clock] = createHttpClientMessage([
        'logMinTimeMS' => 0,
        'logRequestBody' => false,
        'logResponseBody' => true,
        'logRequestBodyLimitSize' => 6,
        'logResponseBodyLimitSize' => 5,
    ]);

    // 全局配置，request body 跳过，response body 截断
    $message->markRequestStart('POST', 'https://example.com/a', [
        'body' => 'request-body',
    ]);
    $clock->sleep(0.3);
    $message->markResponseEnd(['status' => 200, 'body' => 'response-one']);

    // 单次请求覆盖 logRequestBody/logResponseBody
    $message->markRequestStart('POST', 'https://example.com/b', [
        'body' => 'request-body-2',
        '_logger' => [
            'logRequestBody' => true,
            'logRequestBodyLimitSize' => 4,
            'logResponseBody' => false,
        ],
    ]);
    $clock->sleep(0.4);
    $message->markResponseEnd(['status' => 201, 'body' => 'response-two']);

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(2);

    $global = $logs[0]['context'];
    expect($global['request_options']['body'])->toBe('[skip]')
        ->and($global['response_content'])->toBe('respo...');

    $override = $logs[1]['context'];
    expect($override['request_options']['body'])->toBe('requ...')
        ->and(array_key_exists('response_content', $override))->toBeFalse();
});

/**
 * @return array{0: FakeHttpClientMessage, 1: MockClock}
 */
function createHttpClientMessage(array $config = []): array
{
    $message = new FakeHttpClientMessage($config);
    $clock = new MockClock('2024-01-01 00:00:00');
    $message->setClock($clock);

    TestLogger::channel('httpClient')->flush();

    return [$message, $clock];
}
