<?php

use app\process\Http;
use support\Log;
use support\Request;

global $argv;

return [
    'webman' => [
        'handler' => Http::class,
        // e2e 端口可由环境变量覆盖；默认用 18787（8787 + 10000）避开 webman 官方默认端口，
        // 防止与本地在用的常规 webman 项目冲突（改动需同步 WebmanServer::findFreePort 的 fallback）
        'listen' => 'http://0.0.0.0:' . (getenv('E2E_WEBMAN_PORT') ?: 18787),
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
