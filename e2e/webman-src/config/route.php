<?php

use app\controller\AuthController;
use app\controller\DtoController;
use app\controller\EchoController;
use Webman\Route;
use WebmanTech\Auth\Middleware\Authentication;

Route::get('/health', fn() => json(['status' => 'ok']));

Route::group('/echo', function () {
    Route::get('/get', [EchoController::class, 'get']);
    Route::post('/post-json', [EchoController::class, 'postJson']);
    Route::post('/post-form', [EchoController::class, 'postForm']);
    Route::get('/session', [EchoController::class, 'session']);
    Route::get('/session-get', [EchoController::class, 'sessionGet']);
    Route::get('/response', [EchoController::class, 'response']);
});

Route::group('/auth', function () {
    Route::post('/login', [AuthController::class, 'login']);
    // 受保护路由：认证在中间件内完成，失败由 guard 的 failureHandler 返回 401
    Route::get('/user', [AuthController::class, 'user'])->middleware([Authentication::class]);
});

Route::post('/dto/create-user', [DtoController::class, 'createUser']);
