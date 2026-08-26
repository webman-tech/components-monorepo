<?php

// common-utils 的各 facade 在真实 laravel 环境下（\request()/Symfony 分支）的行为

use WebmanTech\CommonUtils\Container;
use WebmanTech\CommonUtils\Runtime;
use function WebmanTech\CommonUtils\{base_path, config_path, runtime_path};

test('Request facade 走 laravel request 分支', function () {
    $response = $this->get('/e2e/echo?q=hello', ['X-Custom' => 'custom-value']);

    $response->assertOk()
        ->assertJson([
            'method' => 'GET',
            'path' => '/e2e/echo',
            'query' => 'hello',
            'header' => 'custom-value',
            'ip' => '127.0.0.1',
        ]);
});

test('Request facade 解析 POST json body', function () {
    $response = $this->postJson('/e2e/echo-json', ['k' => 'json-value']);

    $response->assertOk()
        ->assertJson(['json' => 'json-value']);
});

test('Request facade 解析 POST form body', function () {
    $response = $this->post('/e2e/echo-form', ['f' => 'form-value']);

    $response->assertOk()
        ->assertJson(['form' => 'form-value']);
});

test('Session facade 走 laravel session 分支', function () {
    $response = $this->post('/e2e/session');

    $response->assertOk()
        ->assertJson(['value' => 'e2e_value']);
});

test('Response facade 走 SymfonyResponse 分支', function () {
    $response = $this->get('/e2e/response');

    expect($response->getStatusCode())->toBe(201)
        ->and($response->headers->get('X-E2E'))->toBe('yes')
        ->and($response->getContent())->toBe(json_encode(['ok' => true]));
});

test('Container facade 走 laravel 容器分支', function () {
    $container = Container::getCurrent();

    expect($container->getRaw())->toBe(\Illuminate\Container\Container::getInstance())
        ->and($container->get(\Illuminate\Contracts\Console\Kernel::class))->toBeInstanceOf(\Illuminate\Contracts\Console\Kernel::class);
});

test('Runtime terminating 注册到 laravel 的 terminating 回调', function () {
    $terminated = false;
    Runtime::terminating(function () use (&$terminated) {
        $terminated = true;
    });

    app()->terminate();

    expect($terminated)->toBeTrue();
});

test('路径函数映射到 laravel 路径', function () {
    expect(base_path())->toBe(\base_path())
        ->and(config_path())->toBe(\config_path())
        // laravel 下 runtime 路径映射到 storage
        ->and(runtime_path())->toBe(\storage_path());
});
