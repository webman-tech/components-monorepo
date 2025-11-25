<?php

use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use WebmanTech\CommonUtils\Testing\TestLogger;
use WebmanTech\Logger\Message\SymfonyHttpClientMessage;

beforeEach(function () {
    TestLogger::channel('httpClient')->flush();
});

test('symfony http client logs response info', function () {
    [$message, $clock] = createSymfonyMessage([
        'logMinTimeMS' => 0,
        'logResponseBodyLimitSize' => 5,
    ]);

    $client = new MockHttpClient([
        new MockResponse(str_repeat('A', 10), ['http_code' => 201]),
    ]);
    $response = $client->request('GET', 'https://example.com/path');

    $message->markRequestStart('GET', 'https://example.com/path', []);
    $clock->sleep(0.5);
    $message->markResponseEnd($response);

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(1);
    $context = $logs[0]['context'];

    expect($logs[0]['level'])->toBe('INFO')
        ->and($context['response_status'])->toBe(201)
        ->and($context['response_content'])->toBe('AAAAA...')
        ->and($context['message'] ?? null)->toBeNull(); // ensure context doesn't contain own message
});

test('symfony http client handles response errors', function () {
    [$message, $clock] = createSymfonyMessage([
        'logMinTimeMS' => 0,
    ]);

    // getContent 抛异常
    $message->markRequestStart('POST', 'https://example.com/error', []);
    $clock->sleep(0.1);
    $response = new class extends MockResponse {
        public function __construct()
        {
            parent::__construct('data', ['http_code' => 202]);
        }

        public function getContent(bool $throw = true): string
        {
            throw new RuntimeException('broken content');
        }
    };
    $message->markResponseEnd($response);

    // 非 ResponseInterface
    $message->markRequestStart('DELETE', 'https://example.com/type', []);
    $clock->sleep(0.1);
    $message->markResponseEnd('invalid');

    $logs = TestLogger::channel('httpClient')->getAll();
    expect($logs)->toHaveCount(2);

    $contextError = $logs[0]['context'];
    expect($contextError['response_status'])->toBe(202)
        ->and($contextError['response_content'])->toBe('[Response Content error: broken content]');

    $contextInvalid = $logs[1]['context'];
    expect($contextInvalid['response_status'])->toBe(0)
        ->and($contextInvalid['response_content'])->toBe('[Response Type error]');
});

/**
 * @return array{0: SymfonyHttpClientMessage, 1: MockClock}
 */
function createSymfonyMessage(array $config = []): array
{
    $message = new SymfonyHttpClientMessage($config);
    $clock = new MockClock('2024-01-01 00:00:00');
    $message->setClock($clock);

    return [$message, $clock];
}
