<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use WebmanTech\Logger\Middleware\HttpRequestLogMiddleware;
use WebmanTech\Logger\Middleware\RequestTraceMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // e2e：web 组前置 logger 包的两个中间件（handle() 分支为 laravel 入口）
        $middleware->web(prepend: [
            RequestTraceMiddleware::class,
            HttpRequestLogMiddleware::class,
        ]);
        // e2e：测试路由免除 CSRF（feature 测试的 post 不携带 token）
        $middleware->validateCsrfTokens(except: ['e2e/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
