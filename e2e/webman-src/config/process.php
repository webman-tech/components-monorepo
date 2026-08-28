<?php

use app\process\Http;
use support\Log;
use support\Request;

global $argv;

return [
    'webman' => [
        'handler' => Http::class,
        // 测试端口经 phpunit.xml 注入 APP_PORT 切换（业务运行时保持默认 8787）
        'listen' => 'http://0.0.0.0:' . (getenv('APP_PORT') ?: 8787),
        // 单进程，便于断言日志等共享状态
        'count' => 1,
        'user' => '',
        'group' => '',
        'reusePort' => false,
        'eventLoop' => '',
        'context' => [],
        'constructor' => [
            'requestClass' => Request::class,
            'logger' => Log::channel('default'),
            'appPath' => app_path(),
            'publicPath' => public_path(),
        ],
    ],
    // 不启用 monitor（文件监控 reload）进程：e2e 下无意义且会干扰进程管理
];
