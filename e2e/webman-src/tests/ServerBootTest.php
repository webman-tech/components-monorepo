<?php

// 真实 webman 进程启动/健康检查/停止的基础验证

test('server 可启动并响应 health', function () {
    $response = e2e_request('GET', '/health');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->toArray())->toBe(['status' => 'ok']);
});

test('未注册路由返回 404', function () {
    $response = e2e_request('GET', '/not-exists-route');

    expect($response->getStatusCode())->toBe(404);
});
