<?php

// auth 包 + tinywan/jwt 在真实 webman 环境下的 JWT 认证链路验证

test('登录可签发 JWT token', function () {
    $data = e2e_json('POST', '/auth/login');

    expect($data)->toHaveKeys(['token_type', 'expires_in', 'access_token'])
        ->and($data['token_type'])->toBe('Bearer')
        ->and($data['access_token'])->toBeString()->not->toBeEmpty();
});

test('携带有效 token 可获取当前用户', function () {
    $login = e2e_json('POST', '/auth/login');

    $response = e2e_request('GET', '/auth/user', [
        'auth_bearer' => $login['access_token'],
    ]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->toArray())->toBe([
            'id' => 'e2e-user-1',
            'name' => 'e2e-user',
        ]);
});

test('无 token 访问受保护接口返回 401', function () {
    $response = e2e_request('GET', '/auth/user');

    expect($response->getStatusCode())->toBe(401);
});

test('无效 token 返回 401', function () {
    $response = e2e_request('GET', '/auth/user', [
        'auth_bearer' => 'invalid-token',
    ]);

    expect($response->getStatusCode())->toBe(401);
});
