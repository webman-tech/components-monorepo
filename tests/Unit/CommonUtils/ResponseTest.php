<?php

use Symfony\Component\HttpFoundation\Response as ComponentSymfonyResponse;
use Webman\Http\Response as ComponentWebmanResponse;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Testing\TestResponse;

test('normalize usage', function () {
    $response = Response::make()
        ->withStatus(201, 'created')
        ->withHeaders(['X-Header' => 'value'])
        ->withBody('hello');

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getHeader('X-Header'))->toBe('value')
        ->and($response->getBody())->toBe('hello');

    $rawResponse = $response->toRaw();
    /** @var TestResponse $rawResponse */
    expect($rawResponse)->toBeInstanceOf(TestResponse::class)
        ->and($rawResponse->getStatusCode())->toBe(201)
        ->and($rawResponse->getReasonPhrase())->toBe('created')
        ->and($rawResponse->rawBody())->toBe('hello')
        ->and($rawResponse->getHeader('X-Header'))->toBe('value');

    $response = Response::from($rawResponse);
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->toRaw())->toBe($rawResponse);
});

test('webman response adapter', function () {
    $webmanResponse = new ComponentWebmanResponse(200, ['X-Webman' => 'initial'], 'body');
    $response = Response::from($webmanResponse);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getBody())->toBe('body')
        ->and($response->getHeader('X-Webman'))->toBe('initial');

    $response->withStatus(204)
        ->withHeaders(['X-Another' => 'value'])
        ->withBody('updated');

    expect($response->getStatusCode())->toBe(204)
        ->and($response->getBody())->toBe('updated')
        ->and($response->getHeader('X-Webman'))->toBe('initial')
        ->and($response->getHeader('X-Another'))->toBe('value');

    /** @var ComponentWebmanResponse $raw */
    $raw = $response->toRaw();
    expect($raw)->toBeInstanceOf(ComponentWebmanResponse::class)
        ->and($raw->getStatusCode())->toBe(204)
        ->and($raw->rawBody())->toBe('updated')
        ->and($raw->getHeader('X-Webman'))->toBe('initial')
        ->and($raw->getHeader('X-Another'))->toBe('value');
});

test('symfony response adapter', function () {
    $symfonyResponse = new ComponentSymfonyResponse('<old/>', 202, ['X-Symfony' => 'initial']);
    $response = Response::from($symfonyResponse);

    expect($response->getStatusCode())->toBe(202)
        ->and($response->getBody())->toBe('<old/>')
        ->and($response->getHeader('X-Symfony'))->toBe('initial');

    $response->withStatus(418, 'teapot')
        ->withHeaders(['X-Symfony' => 'changed', 'X-New' => 'value'])
        ->withBody('{"json":true}');

    expect($response->getStatusCode())->toBe(418)
        ->and($response->getBody())->toBe('{"json":true}')
        ->and($response->getHeader('X-Symfony'))->toBe('changed')
        ->and($response->getHeader('X-New'))->toBe('value');

    /** @var ComponentSymfonyResponse $raw */
    $raw = $response->toRaw();
    expect($raw)->toBeInstanceOf(ComponentSymfonyResponse::class)
        ->and($raw->getStatusCode())->toBe(418)
        ->and($raw->getContent())->toBe('{"json":true}')
        ->and($raw->headers->get('X-Symfony'))->toBe('changed')
        ->and($raw->headers->get('X-New'))->toBe('value');
});

test('custom response adapter', function () {
    $testResponse = (new TestResponse())
        ->withStatus(200, 'ok')
        ->withHeaders(['X-Test' => 'abc'])
        ->withBody('initial');

    $response = Response::from($testResponse);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getHeader('X-Test'))->toBe('abc')
        ->and($response->getBody())->toBe('initial')
        ->and($response->getHeader('missing'))->toBeNull();

    $response->withStatus(503, 'maintenance')
        ->withHeaders(['X-New' => 'xyz'])
        ->withBody('updated');

    expect($response->getStatusCode())->toBe(503)
        ->and($response->getHeader('X-New'))->toBe('xyz')
        ->and($response->getBody())->toBe('updated');

    expect($testResponse->getStatusCode())->toBe(503)
        ->and($testResponse->getReasonPhrase())->toBe('maintenance')
        ->and($testResponse->getHeader('X-New'))->toBe('xyz')
        ->and($testResponse->rawBody())->toBe('updated');
});
