<?php

use Symfony\Component\HttpFoundation\Request as ComponentSymfonyRequest;
use Webman\Http\Request as ComponentWebmanRequest;
use Webman\Route\Route as WebmanRoute;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Testing\TestRequest;

test('request current wraps runtime request', function () {
    $request = Request::getCurrent();

    expect($request)->toBeInstanceOf(Request::class)
        ->and($request?->getOriginalRequest())->toBeInstanceOf(TestRequest::class);
});

test('symfony adapter', function () {
    $originalProxies = ComponentSymfonyRequest::getTrustedProxies();
    $originalHeaders = ComponentSymfonyRequest::getTrustedHeaderSet();

    $base = ComponentSymfonyRequest::create(
        '/demo/77?foo=bar',
        'POST',
        ['form_key' => 'form_value'],
        ['session' => 'xyz'],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_TRACE_ID' => 'trace-symfony',
            'REMOTE_ADDR' => '198.51.100.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.20, 10.0.0.1',
            'HTTP_HOST' => 'api.example.com',
        ],
        json_encode(['json_key' => 'symfony_value'])
    );

    $request = Request::from($base);

    expect($request->getMethod())->toBe('POST')
        ->and($request->getPath())->toBe('/demo/77')
        ->and($request->getUserIp())->toBe('203.0.113.20')
        ->and($request->get('foo'))->toBe('bar')
        ->and($request->allGet())->toBe(['foo' => 'bar'])
        ->and($request->path('id'))->toBeNull()
        ->and($request->post('form_key'))->toBe('form_value')
        ->and($request->postForm('form_key'))->toBe('form_value')
        ->and($request->allPostForm())->toMatchArray(['form_key' => 'form_value'])
        ->and($request->header('x-trace-id'))->toBe('trace-symfony')
        ->and($request->getHost())->toBe('api.example.com')
        ->and($request->cookie('session'))->toBe('xyz')
        ->and($request->rawBody())->toBe(json_encode(['json_key' => 'symfony_value']))
        ->and($request->postJson('json_key'))->toBe('symfony_value')
        ->and($request->allPostJson())->toBe(['json_key' => 'symfony_value'])
        ->and($request->withHeaders(['X-New-Header' => 'new-value'])->header('x-new-header'))->toBe('new-value');

    expect(ComponentSymfonyRequest::getTrustedProxies())->toBe($originalProxies)
        ->and(ComponentSymfonyRequest::getTrustedHeaderSet())->toBe($originalHeaders);
});

test('webman adapter', function () {
    $body = 'json_key=json_value';
    $buffer = implode("\r\n", [
        'POST /demo/123?foo=bar HTTP/1.1',
        'Host: example.com',
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($body),
        'X-Trace-Id: trace-webman',
        'Cookie: session=abc; theme=dark',
        '',
        $body,
    ]);
    $webmanRequest = new ComponentWebmanRequest($buffer);
    $route = new WebmanRoute(['POST'], '/demo/{id}', static function () {
    });
    $route->setParams(['id' => '123']);
    $webmanRequest->route = $route;

    $request = Request::from($webmanRequest);

    expect($request->getMethod())->toBe('POST')
        ->and($request->getPath())->toBe('/demo/123')
        ->and($request->getContentType())->toBe('application/x-www-form-urlencoded')
        ->and($request->get('foo'))->toBe('bar')
        ->and($request->allGet())->toBe(['foo' => 'bar'])
        ->and($request->path('id'))->toBe('123')
        ->and($request->header('x-trace-id'))->toBe('trace-webman')
        ->and($request->cookie('session'))->toBe('abc')
        ->and($request->cookie('theme'))->toBe('dark')
        ->and($request->getHost())->toBe('example.com')
        ->and($request->allPostForm())->toMatchArray(['json_key' => 'json_value'])
        ->and($request->post('json_key'))->toBe('json_value')
        ->and($request->postForm('json_key'))->toBe('json_value')
        ->and($request->postJson('json_key'))->toBe('json_value')
        ->and($request->allPostJson())->toBe(['json_key' => 'json_value'])
        ->and($request->rawBody())->toBe($webmanRequest->rawBody())
        ->and($request->getUserIp())->toBe($webmanRequest->getRealIp())
        ->and($request->withHeaders(['X-Injected' => 'abc'])->header('x-injected'))->toBe('abc');
});

test('custom adapter', function () {
    $testRequest = new TestRequest();
    $testRequest->setData([
        'method' => 'patch',
        'path' => '/testing/9',
        'rawBody' => '{"json_key":"json_value"}',
        'pathParams' => ['id' => 9],
        'cookies' => ['token' => 'abc'],
        'userIp' => '10.0.0.1',
    ]);
    $testRequest->setGet(['foo' => 'bar']);
    $testRequest->setPost(['json_key' => 'json_value']);
    $testRequest->setHeader([
        'Content-Type' => 'application/json',
        'Host' => 'cli.test',
        'X-Trace-Id' => 'trace-testing',
    ]);

    $request = Request::from($testRequest);

    expect($request->getMethod())->toBe('PATCH')
        ->and($request->getPath())->toBe('/testing/9')
        ->and($request->getContentType())->toBe('application/json')
        ->and($request->get('foo'))->toBe('bar')
        ->and($request->allGet())->toBe(['foo' => 'bar'])
        ->and($request->post('json_key'))->toBe('json_value')
        ->and($request->postJson('json_key'))->toBe('json_value')
        ->and($request->allPostJson())->toBe(['json_key' => 'json_value'])
        ->and($request->path('id'))->toBe('9')
        ->and($request->header('x-trace-id'))->toBe('trace-testing')
        ->and($request->getHost())->toBe('cli.test')
        ->and($request->cookie('token'))->toBe('abc')
        ->and($request->rawBody())->toBe('{"json_key":"json_value"}')
        ->and($request->getUserIp())->toBe('10.0.0.1');

    $request->withHeaders(['X-New-Header' => 'xyz']);
    expect($request->header('x-new-header'))->toBe('xyz')
        ->and($testRequest->header('x-new-header'))->toBe('xyz');
});
