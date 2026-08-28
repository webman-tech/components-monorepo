<?php

test('登录可签发 JWT token', function () {
    $data = $this->postJson('/auth/login')->assertOk()->json();

    expect($data)->toHaveKeys(['token_type', 'expires_in', 'access_token'])
        ->and($data['token_type'])->toBe('Bearer')
        ->and($data['access_token'])->toBeString()->not->toBeEmpty();
});

test('携带有效 token 可获取当前用户', function () {
    $token = $this->postJson('/auth/login')->json('access_token');

    $this->withToken($token)->getJson('/auth/user')
        ->assertOk()
        ->assertJson([
            'id' => 'e2e-user-1',
            'name' => 'e2e-user',
        ]);
});

test('无 token 访问受保护接口返回 401', function () {
    $this->getJson('/auth/user')->assertUnauthorized();
});

test('无效 token 返回 401', function () {
    $this->withToken('invalid-token')->getJson('/auth/user')->assertUnauthorized();
});
