<?php

use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\Response as ComponentSymfonyResponse;
use Tests\Fixtures\CommonUtils\MultiUsageMiddleware;
use Webman\Http\Request as ComponentWebmanRequest;
use Webman\Http\Response as ComponentWebmanResponse;

test('base middleware adapts webman request/response', function () {
    $buffer = implode("\r\n", [
        'GET /demo HTTP/1.1',
        'Host: example.com',
        '',
        '',
    ]);
    $webmanRequest = new ComponentWebmanRequest($buffer);
    $middleware = new MultiUsageMiddleware();
    $handledRequest = null;

    $response = $middleware->process($webmanRequest, function ($originalRequest) use (&$handledRequest) {
        $handledRequest = $originalRequest;
        expect($originalRequest)->toBeInstanceOf(ComponentWebmanRequest::class)
            ->and($originalRequest->header('x-request-abc'))->toBe('abc');

        return new ComponentWebmanResponse(200, [], 'webman-body');
    });

    expect($handledRequest)->toBe($webmanRequest)
        ->and($webmanRequest->header('x-request-abc'))->toBe('abc')
        ->and($response)->toBeInstanceOf(ComponentWebmanResponse::class)
        ->and($response->getHeader('X-Response-Abc'))->toBe('xyz');
});

test('base middleware adapts laravel request/response', function () {
    $laravelRequest = IlluminateRequest::create('/users', 'POST');
    $middleware = new MultiUsageMiddleware();
    $handledRequest = null;

    $response = $middleware->handle($laravelRequest, function ($originalRequest) use (&$handledRequest) {
        $handledRequest = $originalRequest;
        expect($originalRequest)->toBeInstanceOf(IlluminateRequest::class)
            ->and($originalRequest->headers->get('X-Request-Abc'))->toBe('abc');

        return new ComponentSymfonyResponse('laravel-body', 202);
    });

    expect($handledRequest)->toBe($laravelRequest)
        ->and($laravelRequest->headers->get('X-Request-Abc'))->toBe('abc')
        ->and($response)->toBeInstanceOf(ComponentSymfonyResponse::class)
        ->and($response->headers->get('X-Response-Abc'))->toBe('xyz');
});
