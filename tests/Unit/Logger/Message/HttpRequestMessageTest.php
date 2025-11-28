<?php

use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\Testing\TestLogger;
use WebmanTech\CommonUtils\Testing\TestRequest;
use WebmanTech\CommonUtils\Testing\TestResponse;
use WebmanTech\Logger\Message\HttpRequestMessage;

beforeEach(function () {
    TestLogger::channel('httpRequest')->flush();
});

test('simple request log', function () {
    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'logRequestBodyLimitSize' => 200,
        'extraInfo' => function ($request) {
            return ['tenant' => $request->getUserIp()];
        },
    ]);
    $message->appendLogRequestBodySensitive('token');

    $request = (new TestRequest())
        ->setData('method', 'POST')
        ->setData('path', '/api/users')
        ->setData('userIp', '10.1.1.1')
        ->setGet('foo', 1)
        ->setGet('bar', 'two');

    $body = json_encode(['password' => '123456', 'token' => 'abc', 'name' => 'neo']);
    $request->setHeader('content-type', 'application/json')
        ->setHeader('content-length', (string)strlen((string)$body))
        ->setData('rawBody', (string)$body);

    $message->markRequestStart($request);
    $clock->sleep(0.789);
    $response = (new TestResponse())->withStatus(201);
    $message->markResponseEnd($response, new \RuntimeException('boom'));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1);

    $log = $logs[0];
    expect($log['level'])->toBe('WARNING')
        ->and($log['message'])->toBe('POST:/api/users');

    $context = $log['context'];
    expect($context['cost'])->toBe(789)
        ->and($context['method'])->toBe('POST')
        ->and($context['path'])->toBe('/api/users')
        ->and($context['query'])->toBe('foo=1&bar=two')
        ->and($context['body'])->toBe('{"password":"***","token":"***","name":"neo"}')
        ->and($context['response_status'])->toBe(201)
        ->and($context['response_exception'])->toBe('boom')
        ->and($context['tenant'])->toBe('10.1.1.1');
});

test('skip request by path or closure', function () {
    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'skipRequest' => function ($request) {
            return $request->get('skip') === '1';
        },
    ]);
    $message->appendSkipPaths('/\\/ignore\\//');

    $send = function (string $path, array $query = []) use ($message, $clock) {
        $request = (new TestRequest())
            ->setData('method', 'GET')
            ->setData('path', $path);
        foreach ($query as $key => $value) {
            $request->setGet($key, $value);
        }

        $message->markRequestStart($request);
        $clock->sleep(0.5);
        $response = (new TestResponse())->withStatus(200);
        $message->markResponseEnd($response);
    };

    $send('/ignore/path');
    $send('/visible', ['skip' => '1']);
    $send('/visible');

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]['message'])->toBe('GET:/visible');
});

test('time and response levels', function () {
    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'warningTimeMS' => 1500,
        'errorTimeMS' => 3000,
    ]);

    $send = function (float $seconds, int $status = 200, ?\RuntimeException $exception = null) use ($message, $clock) {
        $request = (new TestRequest())
            ->setData('method', 'GET')
            ->setData('path', '/check');

        $message->markRequestStart($request);
        $clock->sleep($seconds);
        $response = (new TestResponse())->withStatus($status);
        $message->markResponseEnd($response, $exception);
    };

    $send(0.5, 200);
    $send(2.0, 200);
    $send(4.0, 200);
    $send(0.5, 404);
    $send(0.5, 503);
    $send(0.5, 200, new \RuntimeException('boom'));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(6)
        ->and($logs[0]['level'])->toBe('INFO')
        ->and($logs[1]['level'])->toBe('WARNING')
        ->and($logs[2]['level'])->toBe('ERROR')
        ->and($logs[3]['level'])->toBe('WARNING')
        ->and($logs[4]['level'])->toBe('ERROR')
        ->and($logs[5]['level'])->toBe('WARNING')
        ->and($logs[5]['context']['response_exception'])->toBe('boom');
});

test('request query and body logging options', function () {
    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'logRequestQuery' => false,
        'logRequestBody' => false,
    ]);

    $request = (new TestRequest())
        ->setData('method', 'GET')
        ->setData('path', '/config')
        ->setGet('foo', 'bar')
        ->setHeader('content-type', 'application/x-www-form-urlencoded')
        ->setHeader('content-length', '20')
        ->setData('rawBody', 'password=123');

    $message->markRequestStart($request);
    $clock->sleep(0.2);
    $message->markResponseEnd((new TestResponse())->withStatus(200));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1);
    $context = $logs[0]['context'];
    expect(array_key_exists('query', $context))->toBeFalse()
        ->and(array_key_exists('body', $context))->toBeFalse();

    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'logRequestQueryFn' => function ($request) {
            return 'custom=' . $request->get('foo');
        },
        'logRequestBodyFn' => function ($request) {
            if ($request->get('foo') === 'skip') {
                return false;
            }
            return '[override:' . $request->get('foo') . ']';
        },
    ]);

    $request = (new TestRequest())
        ->setData('method', 'POST')
        ->setData('path', '/custom')
        ->setGet('foo', 'bar');
    $message->markRequestStart($request);
    $clock->sleep(0.2);
    $message->markResponseEnd((new TestResponse())->withStatus(200));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]['context']['query'])->toBe('custom=bar')
        ->and($logs[0]['context']['body'])->toBe('[override:bar]');

    $request = (new TestRequest())
        ->setData('method', 'POST')
        ->setData('path', '/custom')
        ->setGet('foo', 'skip');
    $message->markRequestStart($request);
    $clock->sleep(0.2);
    $message->markResponseEnd((new TestResponse())->withStatus(200));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1);
    $lastContext = $logs[0]['context'];
    expect($lastContext['query'])->toBe('custom=skip')
        ->and(array_key_exists('body', $lastContext))->toBeFalse();
});

test('form request body masking', function () {
    [$message, $clock] = createHttpRequestMessage([
        'logMinTimeMS' => 0,
        'logRequestBodyLimitSize' => 100,
    ]);
    $message->appendLogRequestBodySensitive('token');

    $formBody = 'password=123456&token=abc&name=neo';
    $request = (new TestRequest())
        ->setData('method', 'POST')
        ->setData('path', '/form')
        ->setHeader('content-type', 'application/x-www-form-urlencoded')
        ->setHeader('content-length', (string)strlen($formBody))
        ->setData('rawBody', $formBody);

    $message->markRequestStart($request);
    $clock->sleep(0.2);
    $message->markResponseEnd((new TestResponse())->withStatus(200));

    $logs = TestLogger::channel('httpRequest')->getAll();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]['context']['body'])->toBe('password=***&token=***&name=neo');
});

/**
 * @return array{0: HttpRequestMessage, 1: MockClock}
 */
function createHttpRequestMessage(array $config = []): array
{
    $message = new HttpRequestMessage($config);
    $clock = new MockClock('2024-01-01 00:00:00');
    $message->setClock($clock);

    TestLogger::channel('httpRequest')->flush();

    return [$message, $clock];
}
