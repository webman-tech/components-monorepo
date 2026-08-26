<?php

// dto 包在真实 webman 环境下（laravel-monorepo 提供的 validator()）的验证链路

test('合法数据通过 DTO 验证', function () {
    $response = e2e_request('POST', '/dto/create-user', [
        'json' => ['name' => 'webman', 'age' => 3, 'email' => 'a@b.c'],
    ]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->toArray())->toBe([
            'code' => 0,
            'data' => [
                'name' => 'webman',
                'age' => 3,
                'email' => 'a@b.c',
            ],
        ]);
});

test('非法数据返回 422 与验证错误结构', function () {
    $response = e2e_request('POST', '/dto/create-user', [
        'json' => ['name' => 'x', 'age' => 999],
    ]);

    expect($response->getStatusCode())->toBe(422);

    // toArray(false)：4xx 响应体读取不抛 ClientException（Symfony HttpClient 原生行为）
    $data = $response->toArray(false);
    expect($data['code'])->toBe(422)
        ->and($data['errors'])->toBeArray()
        ->and($data['errors'])->toHaveKeys(['name', 'age'])
        ->and($data['errors']['name'])->toBeArray()
        ->and($data['errors']['age'])->toBeArray();
});
