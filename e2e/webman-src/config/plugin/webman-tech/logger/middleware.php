<?php

// 在插件默认中间件（RequestTraceMiddleware + ResetLog）基础上追加请求日志中间件
return [
    '' => [
        WebmanTech\Logger\Middleware\RequestTraceMiddleware::class,
        WebmanTech\Logger\Middleware\ResetLog::class,
        WebmanTech\Logger\Middleware\HttpRequestLogMiddleware::class,
    ],
];
