<?php

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Webman\Http\Response as WebmanResponse;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Testing\TestResponse;

test('make', function () {
    expect(Response::make()->getRaw())->toBeInstanceOf(TestResponse::class);
});

describe('different adapter test', function () {
    $initialStatus = 200;
    $initialBody = 'initial-body';
    $initialHeaders = ['X-Initial' => 'initial'];
    $mutatedStatusCode = 418;
    $mutatedReason = 'teapot';
    $mutatedBody = 'updated-body';
    $mutatedHeaders = ['X-New' => 'new-value'];
    $expectedHeaders = array_merge($initialHeaders, $mutatedHeaders);

    $cases = [
        [
            'get_response' => fn() => (new TestResponse())
                ->withStatus($initialStatus, 'initial-reason')
                ->withHeaders($initialHeaders)
                ->withBody($initialBody),
            'instance_class' => TestResponse::class,
            'raw_assert' => fn(TestResponse $response) => expect($response->getStatusCode())->toBe($mutatedStatusCode)
                ->and($response->getReasonPhrase())->toBe($mutatedReason)
                ->and($response->rawBody())->toBe($mutatedBody)
                ->and($response->getHeader('X-New'))->toBe('new-value'),
        ],
        [
            'get_response' => fn() => new WebmanResponse($initialStatus, $initialHeaders, $initialBody),
            'instance_class' => WebmanResponse::class,
            'raw_assert' => fn(WebmanResponse $response) => expect($response->getStatusCode())->toBe($mutatedStatusCode)
                ->and($response->getReasonPhrase())->toBe($mutatedReason)
                ->and($response->rawBody())->toBe($mutatedBody)
                ->and($response->getHeader('X-New'))->toBe('new-value'),
        ],
        [
            'get_response' => fn() => new SymfonyResponse($initialBody, $initialStatus, $initialHeaders),
            'instance_class' => SymfonyResponse::class,
            'raw_assert' => fn(SymfonyResponse $response) => expect($response->getStatusCode())->toBe($mutatedStatusCode)
                ->and($response->getContent())->toBe($mutatedBody)
                ->and($response->headers->get('X-New'))->toBe('new-value'),
        ],
    ];

    foreach ($cases as $case) {
        test($case['instance_class'], function () use (
            $case,
            $initialStatus,
            $initialBody,
            $initialHeaders,
            $mutatedStatusCode,
            $mutatedReason,
            $mutatedBody,
            $mutatedHeaders,
            $expectedHeaders
        ) {
            $response = Response::from($case['get_response']());

            // 验证原始类型
            expect($response->getRaw())->toBeInstanceOf($case['instance_class']);

            // 验证初始状态
            expect($response->getStatusCode())->toBe($initialStatus)
                ->and($response->getBody())->toBe($initialBody);
            foreach ($initialHeaders as $key => $value) {
                expect($response->getHeader($key))->toBe($value);
            }

            // 修改数据
            $response->withStatus($mutatedStatusCode, $mutatedReason)
                ->withHeaders($mutatedHeaders)
                ->withBody($mutatedBody);

            // 再次验证
            expect($response->getStatusCode())->toBe($mutatedStatusCode)
                ->and($response->getBody())->toBe($mutatedBody);
            foreach ($expectedHeaders as $key => $value) {
                expect($response->getHeader($key))->toBe($value);
            }

            // 原类型验证
            if (isset($case['raw_assert'])) {
                ($case['raw_assert'])($response->getRaw());
            }
        });
    }
});
