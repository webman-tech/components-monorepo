<?php

use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Testing\TestResponse;

test('sendBody', function () {
    /** @var TestResponse $result */
    $result = Response::make()->sendBody('hello', 201, ['X-Header' => 'value'], 'created');
    expect($result)->toBeInstanceOf(TestResponse::class)
        ->and($result->getStatusCode())->toBe(201)
        ->and($result->getReasonPhrase())->toBe('created')
        ->and($result->rawBody())->toBe('hello')
        ->and($result->getHeader('X-Header'))->toBe('value');
});

test('sendJson', function () {
    /** @var TestResponse $result */
    $result = Response::make()->sendJson(['foo' => 'bar'], 202, ['X-Test' => '1']);
    expect($result->getHeader('Content-Type'))->toBe('application/json')
        ->and($result->getHeader('X-Test'))->toBe('1')
        ->and($result->rawBody())->toBe(json_encode(['foo' => 'bar']));
});
