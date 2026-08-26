<?php

// swagger 包在真实 webman 环境下的 openapi doc 生成验证

test('openapi doc 返回合法 json 并包含 e2e 路由', function () {
    $response = e2e_request('GET', '/openapi/doc');
    expect($response->getStatusCode())->toBe(200);

    $doc = $response->toArray();

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
        ]);
});

test('openapi doc 输出与提交的 fixture 一致', function () {
    $fixturePath = __DIR__ . '/fixtures/openapi.json';
    expect($fixturePath)->toBeFile('请运行 e2e 后将实际输出更新到 fixture（见 e2e/README.md）');

    $doc = e2e_request('GET', '/openapi/doc')->toArray();
    $expected = json_decode((string)file_get_contents($fixturePath), true);

    expect(e2e_array_sort_recursive($doc))->toBe(e2e_array_sort_recursive($expected));
});
