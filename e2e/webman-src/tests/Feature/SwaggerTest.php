<?php

test('openapi doc 返回合法 json 并包含 e2e 路由', function () {
    $doc = $this->get('/openapi/doc')->assertOk()->json();

    expect($doc['openapi'])->toBe('3.1.0')
        ->and($doc['info']['title'])->toBe('App OpenAPI')
        ->and($doc['paths'])->toHaveKeys([
            '/echo/get',
            '/echo/post-json',
            '/echo/post-form',
            '/echo/session',
            '/echo/session-get',
            '/echo/response',
            '/auth/login',
            '/auth/user',
            '/dto/create-user',
        ])
        ->and($doc['components']['schemas']['AmisUser']['properties'])->not->toHaveKeys(['exists', 'incrementing'])
        ->and($doc['components']['schemas']['AmisUser']['properties']['created_at']['type'])->toBe(['string', 'null'])
        ->and($doc['components']['schemas']['CreateUserDTO']['required'])->toBe(['name', 'age']);
});

test('openapi doc 输出与提交的 fixture 一致', function () {
    $fixturePath = __DIR__ . '/fixtures/openapi.json';
    expect($fixturePath)->toBeFile('请运行 e2e 后将实际输出更新到 fixture（见 e2e/README.md）');

    $doc = $this->get('/openapi/doc')->json();
    $expected = json_decode((string)file_get_contents($fixturePath), true);

    expect(e2e_array_sort_recursive($doc))->toBe(e2e_array_sort_recursive($expected));
});
