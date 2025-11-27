<?php

use WebmanTech\CommonUtils\Response;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

test('HostForbiddenMiddleware check', function () {
    $request = request_create_one();
    $originalRequest = request_get_original($request);

    // 仅内网允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => true,
    ]);
    // 内网
    $originalRequest->setData('userIp', '192.168.1.1');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);
    // 外网
    $originalRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(403);

    // 开关关闭的情况下
    $middleware = new HostForbiddenMiddleware([
        'enable' => false,
        'ip_white_list_intranet' => true,
    ]);
    // 外网
    $originalRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);

    // ip 白名单允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'ip_white_list' => ['8.8.8.8'],
    ]);
    // 内网
    $originalRequest->setData('userIp', '192.168.1.1');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    // 外网
    $originalRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);

    // host 白名单
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'host_white_list' => ['example.com'],
    ]);
    $originalRequest->setHeader('host', 'example.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);
    $originalRequest->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
});
