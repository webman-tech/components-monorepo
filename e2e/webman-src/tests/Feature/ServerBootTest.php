<?php

test('server 可启动并响应 health', function () {
    $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
});

test('未注册路由返回 404', function () {
    $this->get('/not-exists-route')->assertNotFound();
});

test('重定向默认不跟随，可断言 302 与 Location', function () {
    $this->get('/redirect')
        ->assertStatus(302)
        ->assertLocation('/health');
});

test('followingRedirects 跟随重定向并断言最终响应', function () {
    // PSR-18 无自动重定向，跟随逻辑为组件手动实现
    $this->followingRedirects()->get('/redirect')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

test('webman 配置文件（config/testing.php）的 httpClient 参数生效', function () {
    // 自动发现的 guzzle 按 config/testing.php 配置构造（默认 timeout=10/connect_timeout=2，此处为 5/1）
    $client = $this->webmanServer()->client();
    expect($client)->toBeInstanceOf(GuzzleHttp\Client::class)
        ->and($client->getConfig('timeout'))->toBe(5)
        ->and($client->getConfig('connect_timeout'))->toBe(1)
        // http_errors 恒为 false，保证 4xx/5xx 交由断言层处理
        ->and($client->getConfig('http_errors'))->toBeFalse();
});
