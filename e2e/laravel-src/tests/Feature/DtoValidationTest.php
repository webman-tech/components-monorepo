<?php

// dto 包在真实 laravel 环境下（框架自带 validator() helper）的验证链路

test('合法数据通过 DTO 验证', function () {
    $response = $this->postJson('/e2e/dto', [
        'name' => 'laravel',
        'age' => 3,
        'email' => 'a@b.c',
    ]);

    $response->assertOk()
        ->assertJson([
            'code' => 0,
            'data' => [
                'name' => 'laravel',
                'age' => 3,
                'email' => 'a@b.c',
            ],
        ]);
});

test('非法数据返回 422 与验证错误结构', function () {
    $response = $this->postJson('/e2e/dto', [
        'name' => 'x',
        'age' => 999,
    ]);

    $response->assertStatus(422);

    $data = $response->json();
    expect($data['code'])->toBe(422)
        ->and($data['errors'])->toBeArray()
        ->and($data['errors'])->toHaveKeys(['name', 'age'])
        ->and($data['errors']['name'])->toBeArray()
        ->and($data['errors']['age'])->toBeArray();
});
