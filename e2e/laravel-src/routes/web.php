<?php

use App\DTO\CreateUserDTO;
use Illuminate\Http\Request;
use WebmanTech\CommonUtils\Log;
use WebmanTech\CommonUtils\Request as CommonRequest;
use WebmanTech\CommonUtils\Response as CommonResponse;
use WebmanTech\DTO\Exceptions\DTOValidateException;

/*
|--------------------------------------------------------------------------
| e2e 测试路由
|--------------------------------------------------------------------------
| 覆盖骨架默认路由，验证 common-utils/dto/logger 包在 laravel 下的真实行为。
*/

Route::get('/e2e/echo', function (Request $request) {
    $req = CommonRequest::from($request);

    return response()->json([
        'method' => $req->getMethod(),
        'path' => $req->getPath(),
        'query' => $req->get('q'),
        'header' => $req->header('x-custom'),
        'ip' => $req->getUserIp(),
    ]);
});

Route::post('/e2e/echo-json', function (Request $request) {
    return response()->json([
        'json' => CommonRequest::from($request)->postJson('k'),
    ]);
});

Route::post('/e2e/echo-form', function (Request $request) {
    return response()->json([
        'form' => CommonRequest::from($request)->postForm('f'),
    ]);
});

Route::post('/e2e/session', function (Request $request) {
    $session = CommonRequest::from($request)->getSession();
    $session->set('e2e_key', 'e2e_value');

    return response()->json([
        'value' => $session->get('e2e_key'),
    ]);
});

Route::get('/e2e/response', function () {
    // Response facade 的 laravel 分支（SymfonyResponse）
    return CommonResponse::make()
        ->withStatus(201)
        ->withHeaders(['X-E2E' => 'yes'])
        ->withBody(json_encode(['ok' => true]))
        ->getRaw();
});

Route::post('/e2e/dto', function () {
    try {
        $dto = CreateUserDTO::fromRequest();
    } catch (DTOValidateException $e) {
        return response()->json([
            'code' => 422,
            'message' => $e->first(),
            'errors' => $e->getErrors(),
        ], 422);
    }

    return response()->json([
        'code' => 0,
        'data' => [
            'name' => $dto->name,
            'age' => $dto->age,
            'email' => $dto->email,
        ],
    ]);
});

Route::get('/e2e/log', function () {
    // Log facade 的 laravel 分支（LaravelLog::channel）
    Log::channel('httpRequest')->info('e2e log message');

    return response()->json(['logged' => true]);
});
