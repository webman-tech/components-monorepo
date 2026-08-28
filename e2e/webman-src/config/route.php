<?php

use app\controller\AmisUserController;
use app\controller\AuthController;
use app\controller\DtoController;
use app\controller\EchoController;
use Webman\Route;
use WebmanTech\AmisAdmin\Controller\RenderController;
use WebmanTech\Auth\Middleware\Authentication;

Route::get('/health', fn() => json(['status' => 'ok']));

// 重定向：跟随与否由测试断言决定（不跟随断言 302 + Location，followingRedirects 断言最终响应）
Route::get('/redirect', fn() => redirect('/health'));

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

// amis-admin：RESTful CRUD（reset 必须先于 {id} 注册，否则会被当作 id 匹配）
Route::post('/amis/users/reset', [AmisUserController::class, 'reset']);
Route::get('/amis/users', [AmisUserController::class, 'index']);
Route::post('/amis/users', [AmisUserController::class, 'store']);
Route::get('/amis/users/{id}', [AmisUserController::class, 'show']);
Route::put('/amis/users/{id}', [AmisUserController::class, 'update']);
Route::delete('/amis/users/{id}', [AmisUserController::class, 'destroy']);
Route::get('/amis/login', [RenderController::class, 'login']);
