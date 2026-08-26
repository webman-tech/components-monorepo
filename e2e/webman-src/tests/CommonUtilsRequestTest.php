<?php

// 真实 webman 环境下 common-utils Request/Session/Response facade 的行为验证

test('Request facade: GET query 与 header', function () {
    $data = e2e_json('GET', '/echo/get?foo=bar', [
        'headers' => ['x-custom-header' => 'custom-value'],
    ]);

    expect($data)->toBe([
        'method' => 'GET',
        'path' => '/echo/get',
        'query' => 'bar',
        'header' => 'custom-value',
        'userIp' => '127.0.0.1',
    ]);
});

test('Request facade: POST json body', function () {
    $data = e2e_json('POST', '/echo/post-json', [
        'json' => ['name' => 'webman', 'age' => 3],
    ]);

    expect($data)->toBe([
        'method' => 'POST',
        'contentType' => 'application/json',
        'name' => 'webman',
        'age' => 3,
    ]);
});

test('Request facade: POST form body', function () {
    $data = e2e_json('POST', '/echo/post-form', [
        'body' => ['name' => 'form-value'],
    ]);

    expect($data)->toBe([
        'method' => 'POST',
        'contentType' => 'application/x-www-form-urlencoded',
        'name' => 'form-value',
    ]);
});

test('Session facade: 跨请求读写（真实 file session）', function () {
    $first = e2e_request('GET', '/echo/session');
    expect($first->getStatusCode())->toBe(200);

    $setCookie = $first->getHeaders()['set-cookie'][0] ?? '';
    expect($setCookie)->toContain('PHPSID=');

    $cookie = strtok($setCookie, ';');
    $second = e2e_request('GET', '/echo/session-get', [
        'headers' => ['cookie' => $cookie],
    ]);

    expect($second->getStatusCode())->toBe(200)
        ->and($second->toArray())->toBe(['e2e_key' => 'e2e_value']);
});

test('RequestTraceMiddleware: custom data 中的 traceId', function () {
    $data = e2e_json('GET', '/echo/session');

    expect($data['traceId'])->toStartWith('trace');
});

test('Response facade: 状态码/header/body', function () {
    $response = e2e_request('GET', '/echo/response');

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getHeaders(false)['x-e2e-response'][0])->toBe('yes')
        ->and($response->getContent())->toBe('created by common-utils Response');
});
