<?php

// amis-admin 在真实 webman 链路下的行为验证：
// AmisSourceController 的 CRUD HTTP 契约（amis 前端强约定的 {status, msg, data} 结构）、
// EloquentRepository + sqlite、LaravelValidator 验证（422 经异常管线转换）、RenderController 渲染

beforeEach(function () {
    // 每个测试前重置为固定数据集（3 条记录）
    $response = e2e_request('POST', '/amis/users/reset');
    expect($response->getStatusCode())->toBe(200);
});

test('index 无 _ajax 时返回页面 schema', function () {
    $data = e2e_json('GET', '/amis/users');

    expect($data['status'])->toBe(0)
        ->and($data['data']['type'])->toBe('page')
        // grid 列 + attributeLabels 的中文 label（buildGridColumn 注入）
        ->and($data['data']['body'][0]['type'])->toBe('crud')
        // create 按钮（withCreate）的表单 api 指向本路由（JSON 斜杠转义为 \/）
        ->and(json_encode($data))->toContain('post:\\/amis\\/users')
        ->and($data['data']['body'][0]['columns'][1]['label'])->toBe('用户名')
        ->and($data['data']['body'][0]['columns'][2]['label'])->toBe('邮箱');
});

test('index 带 _ajax 时返回分页数据', function () {
    $data = e2e_json('GET', '/amis/users?_ajax=1&page=1&perPage=2');

    expect($data['status'])->toBe(0)
        ->and($data['data']['total'])->toBe(3)
        ->and(count($data['data']['items']))->toBe(2)
        ->and($data['data']['items'][0])->toHaveKeys(['id', 'username', 'email', 'created_at', 'updated_at']);
});

test('列表支持按字段搜索', function () {
    $data = e2e_json('GET', '/amis/users?_ajax=1&username=e2e-user-1');

    expect($data['data']['total'])->toBe(1)
        ->and($data['data']['items'][0]['email'])->toBe('user1@e2e.test');
});

test('show 返回单条详情', function () {
    $data = e2e_json('GET', '/amis/users/1');

    expect($data['data']['id'])->toBe(1)
        ->and($data['data']['username'])->toBe('e2e-user-1');
});

test('store 新增数据并可通过列表查询', function () {
    $response = e2e_request('POST', '/amis/users', ['json' => [
        'username' => 'e2e-new',
        'email' => 'new@e2e.test',
    ]]);
    expect($response->getStatusCode())->toBe(200)
        ->and($response->toArray(false)['status'])->toBe(0);

    $data = e2e_json('GET', '/amis/users?_ajax=1&username=e2e-new');
    expect($data['data']['total'])->toBe(1)
        ->and($data['data']['items'][0]['email'])->toBe('new@e2e.test');
});

test('store 验证失败返回 422 及错误结构', function () {
    $response = e2e_request('POST', '/amis/users', ['json' => [
        'username' => '',
        'email' => 'not-an-email',
    ]]);

    // ValidationException 经 config/exception.php 注册的 handler 转为 422
    expect($response->getStatusCode())->toBe(422);

    $data = $response->toArray(false);
    expect($data['status'])->toBe(1)
        ->and($data['data']['errors'])->toHaveKey('username')
        ->and($data['data']['errors'])->toHaveKey('email');
});

test('update 修改数据', function () {
    // amis 的 update form 提交完整表单（required 字段全部提交）
    $response = e2e_request('PUT', '/amis/users/2', ['json' => [
        'username' => 'e2e-updated',
        'email' => 'user2-new@e2e.test',
    ]]);
    expect($response->getStatusCode())->toBe(200);

    $data = e2e_json('GET', '/amis/users/2');
    expect($data['data']['username'])->toBe('e2e-updated')
        ->and($data['data']['email'])->toBe('user2-new@e2e.test');
});

test('destroy 删除数据', function () {
    $response = e2e_request('DELETE', '/amis/users/3');
    expect($response->getStatusCode())->toBe(200);

    $data = e2e_json('GET', '/amis/users?_ajax=1');
    expect($data['data']['total'])->toBe(2);
});

test('login 页渲染包含 amis SDK 与登录表单配置', function () {
    $response = e2e_request('GET', '/amis/login');

    expect($response->getStatusCode())->toBe(200);

    $content = $response->getContent();
    expect($content)->toContain('sdk.js')
        // page_login 配置（覆盖插件默认的 route() 依赖）生效；amisJSON 内嵌于 HTML，斜杠转义为 \/
        ->and($content)->toContain('amis\\/login-api')
        // 登录表单字段
        ->and($content)->toContain('password');
});
