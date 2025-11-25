<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Clock\MockClock;
use WebmanTech\CommonUtils\Testing\TestLogger;
use WebmanTech\Logger\Message\GuzzleHttpClientMessage;

beforeEach(function () {
    TestLogger::channel('httpClient')->flush();
});

test('guzzle http client logs response info', function () {
    [$message, $clock] = createGuzzleMessage([
        'logMinTimeMS' => 0,
        'logResponseBodyLimitSize' => 4,
    ]);

    $message->markRequestStart('PUT', 'https://example.com/items', []);
    $clock->sleep(0.5);
    $message->markResponseEnd(new Response(204, [], 'ABCDEFG'));

    $message->markRequestStart('PUT', 'https://example.com/items', []);
    $clock->sleep(0.5);
    $message->markResponseEnd('invalid response');

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(2);

    $first = $logs[0]['context'];
    expect($logs[0]['level'])->toBe('INFO')
        ->and($first['response_status'])->toBe(204)
        ->and($first['response_content'])->toBe('ABCD...');

    $second = $logs[1]['context'];
    expect($second['response_status'])->toBe(0)
        ->and($second['response_content'])->toBe('[Response Type error]');
});

test('guzzle middleware integrates with handler stack', function () {
    [$message, $clock] = createGuzzleMessage([
        'logMinTimeMS' => 0,
    ]);

    $mock = new MockHandler([
        function (RequestInterface $request, array $options) use ($clock) {
            $clock->sleep(0.6);
            return new Response(200, [], 'ok');
        },
        function (RequestInterface $request, array $options) use ($clock) {
            $clock->sleep(1.2);
            throw new RequestException('boom', $request, new Response(502, [], 'failed'));
        },
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push($message->middleware());

    $client = new Client(['handler' => $stack]);

    $client->request('GET', 'https://example.com/success');

    try {
        $client->request('POST', 'https://example.com/fail');
    } catch (RequestException) {
        // ignore expected exception
    }

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(2);

    $success = $logs[0];
    expect($success['message'])->toBe('GET:/success')
        ->and($success['context']['response_status'])->toBe(200)
        ->and($success['context']['cost'])->toBeGreaterThan(0);

    $failure = $logs[1];
    expect($failure['level'])->toBe('WARNING')
        ->and($failure['message'])->toBe('POST:/fail')
        ->and($failure['context']['response_status'])->toBe(502)
        ->and($failure['context']['response_content'])->toBe('failed')
        ->and($failure['context']['response_exception'])->toBe('boom');
});

/**
 * @return array{0: GuzzleHttpClientMessage, 1: MockClock}
 */
function createGuzzleMessage(array $config = []): array
{
    $message = new GuzzleHttpClientMessage($config);
    $clock = new MockClock('2024-01-01 00:00:00');
    $message->setClock($clock);

    return [$message, $clock];
}
