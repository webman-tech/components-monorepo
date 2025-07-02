<?php

use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

test('check', function () {
    $request = request_create_one();

    // 仅内网允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => true,
    ]);
    // 内网
    $request->setHeader('x-forwarded-for', '192.168.1.1');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);
    // 外网
    $request->setHeader('x-forwarded-for', '8.8.8.8');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);

    // 开关关闭的情况下
    $middleware = new HostForbiddenMiddleware([
        'enable' => false,
        'ip_white_list_intranet' => true,
    ]);
    // 外网
    $request->setHeader('x-forwarded-for', '8.8.8.8');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);

    // ip 白名单允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'ip_white_list' => ['8.8.8.8'],
    ]);
    // 内网
    $request->setHeader('x-forwarded-for', '192.168.1.1');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    // 外网
    $request->setHeader('x-forwarded-for', '8.8.8.8');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);

    // host 白名单
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'host_white_list' => ['example.com'],
    ]);
    $request->setHeader('host', 'example.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);
    $request->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
});
