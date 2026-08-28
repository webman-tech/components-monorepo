<?php

// 守护插件 Install.php 落地的 config/plugin/webman-tech/* 产物

test('webman-tech 插件默认配置已由 Install.php 落地', function (string $package, array $files) {
    $pluginDir = $this->webmanServer()->appDir() . '/config/plugin/webman-tech/' . $package;
    foreach ($files as $file) {
        expect(is_file("{$pluginDir}/{$file}"))->toBeTrue("缺少 {$pluginDir}/{$file}");
    }
})->with([
    'swagger' => ['swagger', ['app.php', 'route.php']],
    'auth' => ['auth', ['app.php', 'auth.php']],
    'logger' => ['logger', ['app.php', 'middleware.php', 'log-channel.php']],
    'crontab-task' => ['crontab-task', ['app.php', 'command.php', 'process.php']],
    'amis-admin' => ['amis-admin', ['amis.php', 'app.php']],
]);

test('amis-admin 翻译文件已由 Install.php 落地', function () {
    $file = $this->webmanServer()->appDir() . '/resource/translations/en/amis-admin.php';
    expect(is_file($file))->toBeTrue("缺少 {$file}");
});

test('swagger 插件路由已注册（plugin route.php 被加载）', function () {
    $response = $this->get('/openapi/doc')->assertOk();

    expect($response->header('content-type'))->toContain('application/json');
});
