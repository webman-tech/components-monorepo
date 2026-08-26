<?php

// 守护 webman 插件安装链路：composer update 触发包内 Install.php，
// 将 copy/config/plugin 复制到应用 config/plugin/webman-tech/* 的产物必须存在

test('webman-tech 插件默认配置已由 Install.php 落地', function (string $package, array $files) {
    $pluginDir = e2e_server()->appDir() . '/config/plugin/webman-tech/' . $package;
    foreach ($files as $file) {
        expect(is_file("{$pluginDir}/{$file}"))->toBeTrue("缺少 {$pluginDir}/{$file}");
    }
})->with([
    'swagger' => ['swagger', ['app.php', 'route.php']],
    'auth' => ['auth', ['app.php', 'auth.php']],
    'logger' => ['logger', ['app.php', 'middleware.php', 'log-channel.php']],
]);

test('swagger 插件路由已注册（plugin route.php 被加载）', function () {
    $response = e2e_request('GET', '/openapi/doc');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getHeaders()['content-type'][0])->toContain('application/json');
});
