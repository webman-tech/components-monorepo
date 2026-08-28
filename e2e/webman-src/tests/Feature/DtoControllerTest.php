<?php

test('合法数据通过 DTO 验证', function () {
    $this->postJson('/dto/create-user', ['name' => 'webman', 'age' => 3, 'email' => 'a@b.c'])
        ->assertOk()
        ->assertJson([
            'code' => 0,
            'data' => [
                'name' => 'webman',
                'age' => 3,
                'email' => 'a@b.c',
            ],
        ]);
});

test('非法数据返回 422 与验证错误结构', function () {
    $this->postJson('/dto/create-user', ['name' => 'x', 'age' => 999])
        ->assertStatus(422)
        ->assertJsonStructure([
            'code',
            'errors' => ['name', 'age'],
        ])
        ->assertJsonPath('code', 422)
        ->assertJsonPath('errors.name', fn($errors) => is_array($errors))
        ->assertJsonPath('errors.age', fn($errors) => is_array($errors));
});
