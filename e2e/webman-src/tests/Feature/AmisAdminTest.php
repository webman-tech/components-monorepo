<?php

beforeEach(function () {
    // 每个测试前重置为固定数据集（3 条记录）
    $this->post('/amis/users/reset')->assertOk();
});

test('index 无 _ajax 时返回页面 schema', function () {
    $response = $this->getJson('/amis/users')
        ->assertOk()
        ->assertJsonPath('status', 0)
        ->assertJsonPath('data.type', 'page')
        // grid 为 crud、create 按钮（withCreate）的表单 api 指向本路由（JSON 斜杠转义为 \/）
        ->assertJsonPath('data.body.0.type', 'crud');

    // create 按钮（withCreate）的表单 api 指向本路由（JSON 斜杠转义为 \/）
    $data = $response->json();
    expect($data['data']['body'][0]['columns'][1]['label'])->toBe('用户名')
        ->and($data['data']['body'][0]['columns'][2]['label'])->toBe('邮箱')
        ->and(json_encode($data))->toContain('post:\\/amis\\/users');
});

test('index 带 _ajax 时返回分页数据', function () {
    $this->getJson('/amis/users?_ajax=1&page=1&perPage=2')
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => [
                'total',
                'items' => ['*' => ['id', 'username', 'email', 'created_at', 'updated_at']],
            ],
        ])
        ->assertJsonPath('status', 0)
        ->assertJsonPath('data.total', 3)
        ->assertJsonCount(2, 'data.items');
});

test('列表支持按字段搜索', function () {
    $this->getJson('/amis/users?_ajax=1&username=e2e-user-1')
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.email', 'user1@e2e.test');
});

test('show 返回单条详情', function () {
    $this->getJson('/amis/users/1')
        ->assertOk()
        ->assertJsonPath('data.id', 1)
        ->assertJsonPath('data.username', 'e2e-user-1');
});

test('store 新增数据并可通过列表查询', function () {
    $this->postJson('/amis/users', [
        'username' => 'e2e-new',
        'email' => 'new@e2e.test',
    ])->assertOk()->assertJsonPath('status', 0);

    $this->getJson('/amis/users?_ajax=1&username=e2e-new')
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.email', 'new@e2e.test');

    $this->assertDatabaseHas('amis_users', ['username' => 'e2e-new', 'email' => 'new@e2e.test']);
});

test('store 验证失败返回 422 及错误结构', function () {
    $this->postJson('/amis/users', [
        'username' => '',
        'email' => 'not-an-email',
    ])
        // ValidationException 经 config/exception.php 注册的 handler 转为 422
        ->assertStatus(422)
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['data' => ['errors' => ['username', 'email']]]);
});

test('update 修改数据', function () {
    // amis 的 update form 提交完整表单（required 字段全部提交）
    $this->putJson('/amis/users/2', [
        'username' => 'e2e-updated',
        'email' => 'user2-new@e2e.test',
    ])->assertOk();

    $this->getJson('/amis/users/2')
        ->assertJsonPath('data.username', 'e2e-updated')
        ->assertJsonPath('data.email', 'user2-new@e2e.test');
});

test('destroy 删除数据', function () {
    $this->delete('/amis/users/3')->assertOk();

    $this->getJson('/amis/users?_ajax=1')->assertJsonPath('data.total', 2);

    $this->assertDatabaseMissing('amis_users', ['username' => 'e2e-user-3']);
});

test('数据库断言（InteractsWithDatabase 跨进程共享库）', function () {
    $this->assertDatabaseCount('amis_users', 3)
        ->assertDatabaseHas('amis_users', ['username' => 'e2e-user-1', 'email' => 'user1@e2e.test'])
        ->assertDatabaseHas('amis_users', ['username' => 'e2e-user-2'])
        ->assertDatabaseMissing('amis_users', ['username' => 'not-exists'])
        // 断言链可继续发请求（数据库连接与 HTTP 断言互不干扰）
        ->getJson('/amis/users?_ajax=1')
        ->assertJsonPath('data.total', 3);
});

test('login 页渲染包含 amis SDK 与登录表单配置', function () {
    $this->get('/amis/login')
        ->assertOk()
        ->assertSee('sdk.js')
        // page_login 配置生效（覆盖插件默认的 route() 依赖）；amisJSON 内嵌于 HTML，斜杠转义为 \/
        ->assertSee('amis\\/login-api')
        ->assertSee('password');
});
