<?php

use WebmanTech\CommonUtils\Response;
use WebmanTech\Swagger\Middleware\BasicAuthMiddleware;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

test('HostForbiddenMiddleware check', function () {
    $request = request_create_one();
    $rawRequest = request_get_raw($request);

    // 仅内网允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => true,
    ]);
    // 内网
    $rawRequest->setData('userIp', '192.168.1.1');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);
    // 外网
    $rawRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(403);

    // 开关关闭的情况下
    $middleware = new HostForbiddenMiddleware([
        'enable' => false,
        'ip_white_list_intranet' => true,
    ]);
    // 外网
    $rawRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);

    // ip 白名单允许
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'ip_white_list' => ['8.8.8.8'],
    ]);
    // 内网
    $rawRequest->setData('userIp', '192.168.1.1');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    // 外网
    $rawRequest->setData('userIp', '8.8.8.8');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);

    // host 白名单
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => null,
        'host_white_list' => ['example.com'],
    ]);
    $rawRequest->setHeader('host', 'example.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);
    $rawRequest->setHeader('host', 'abc.example.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(200);
    $rawRequest->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    
    // forbidden_show_detail 测试 - 显示详细信息
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => false,
        'host_white_list' => ['example.com'],
        'forbidden_show_detail' => true,
    ]);
    $rawRequest->setData('userIp', '8.8.8.8');
    $rawRequest->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    expect($response->getBody())->toBe('Forbidden for ip(8.8.8.8) and host(a.com)');
    
    // forbidden_show_detail 测试 - 不显示详细信息
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => false,
        'host_white_list' => ['example.com'],
        'forbidden_show_detail' => false,
    ]);
    $rawRequest->setData('userIp', '8.8.8.8');
    $rawRequest->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    expect($response->getBody())->toBe('Forbidden');
    
    // forbidden_show_detail 测试 - 自定义消息
    $middleware = new HostForbiddenMiddleware([
        'enable' => true,
        'ip_white_list_intranet' => false,
        'host_white_list' => ['example.com'],
        'forbidden_show_detail' => 'Access Denied',
    ]);
    $rawRequest->setData('userIp', '8.8.8.8');
    $rawRequest->setHeader('host', 'a.com');
    $response = $middleware->process($request, fn() => response('ok'));
    expect($response->getStatusCode())->toBe(403);
    expect($response->getBody())->toBe('Access Denied');
});

test('BasicAuthMiddleware check', function () {
    $request = request_create_one();
    $rawRequest = request_get_raw($request);

    // 关闭时不拦截
    $middleware = new BasicAuthMiddleware(['enable' => false]);
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);

    // 开启但未传 Authorization
    $middleware = new BasicAuthMiddleware([
        'enable' => true,
        'username' => 'admin',
        'password' => 'secret',
    ]);
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(401);
    expect($response->getHeader('WWW-Authenticate'))->toBe('Basic realm="Swagger API Documentation"');

    // 正确的凭证
    $rawRequest->setHeader('authorization', 'Basic ' . base64_encode('admin:secret'));
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(200);

    // 错误的用户名
    $rawRequest->setHeader('authorization', 'Basic ' . base64_encode('wrong:secret'));
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(401);

    // 错误的密码
    $rawRequest->setHeader('authorization', 'Basic ' . base64_encode('admin:wrong'));
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(401);

    // 非 Basic 的 Authorization header
    $rawRequest->setHeader('authorization', 'Bearer sometoken');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getStatusCode())->toBe(401);

    // 自定义 realm
    $middleware = new BasicAuthMiddleware([
        'enable' => true,
        'username' => 'admin',
        'password' => 'secret',
        'realm' => 'My API Docs',
    ]);
    $rawRequest->setHeader('authorization', '');
    $response = $middleware->processRequest($request, fn() => Response::make()->withBody('ok'));
    expect($response->getHeader('WWW-Authenticate'))->toBe('Basic realm="My API Docs"');
});