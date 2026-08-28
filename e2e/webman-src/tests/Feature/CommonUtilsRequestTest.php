<?php

test('Request facade: GET query 与 header', function () {
    $data = $this->get('/echo/get?foo=bar', ['x-custom-header' => 'custom-value'])
        ->assertOk()
        ->json();

    expect($data)->toBe([
        'method' => 'GET',
        'path' => '/echo/get',
        'query' => 'bar',
        'header' => 'custom-value',
        'userIp' => '127.0.0.1',
    ]);
});

test('Request facade: POST json body', function () {
    $data = $this->postJson('/echo/post-json', ['name' => 'webman', 'age' => 3])->json();

    expect($data)->toBe([
        'method' => 'POST',
        'contentType' => 'application/json',
        'name' => 'webman',
        'age' => 3,
    ]);
});

test('Request facade: POST form body', function () {
    $data = $this->post('/echo/post-form', ['name' => 'form-value'])->json();

    expect($data)->toBe([
        'method' => 'POST',
        'contentType' => 'application/x-www-form-urlencoded',
        'name' => 'form-value',
    ]);
});

test('Session facade: 跨请求读写（真实 file session）', function () {
    $first = $this->get('/echo/session')->assertOk();

    $setCookie = $first->header('Set-Cookie');
    expect($setCookie)->toContain('PHPSID=');

    $cookie = strtok($setCookie, ';');
    $second = $this->get('/echo/session-get', ['Cookie' => $cookie]);

    expect($second->assertOk()->json())->toBe(['e2e_key' => 'e2e_value']);
});

test('RequestTraceMiddleware: custom data 中的 traceId', function () {
    $data = $this->getJson('/echo/session')->json();

    expect($data['traceId'])->toStartWith('trace');
});

test('Response facade: 状态码/header/body', function () {
    $this->get('/echo/response')
        ->assertStatus(201)
        ->assertHeader('x-e2e-response', 'yes')
        ->assertSee('created by common-utils Response');
});
