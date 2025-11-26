<?php

use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Testing\TestResponse;

test('sendBody', function () {
    /** @var TestResponse $result */
    $result = Response::make()->sendBody('hello', 201, ['X-Header' => 'value'], 'created');
    expect($result)->toBeInstanceOf(TestResponse::class)
        ->and($result->getStatusCode())->toBe(201)
        ->and($result->getContent())->toBe('hello')
        ->and($result->headers->get('x-header'))->toBe('value');
});

test('sendJson', function () {
    /** @var TestResponse $result */
    $result = Response::make()->sendJson(['foo' => 'bar'], 202, ['X-Test' => '1']);
    expect($result->headers->get('content-type'))->toBe('application/json')
        ->and($result->headers->get('x-test'))->toBe('1')
        ->and($result->getContent())->toBe(json_encode(['foo' => 'bar']));
});
