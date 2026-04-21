<?php

use WebmanTech\CommonUtils\Route;
use WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Middleware\BasicAuthMiddleware;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;
use WebmanTech\Swagger\Swagger;

test('registerGlobalRoute dont regsiter_webman_route', function () {
    ConfigHelper::setForTest('app.global_route', [
        'register_webman_route' => false,
    ]);

    Route::clear();
    $route = Route::getCurrent();

    Swagger::create()->registerGlobalRoute();

    $data = [];
    foreach ($route->getRoutes() as $route) {
        $data[] = [
            'path' => $route->getPath(),
            'methods' => $route->getMethods(),
        ];
    }
    $data = collect($data)->sortBy(fn(array $item) => $item['path'])->values()->toArray(); // 排个序，防止顺序问题
    expect($data)->toMatchSnapshot();

    ConfigHelper::setForTest();
});

test('registerGlobalRoute register_webman_route', function () {
    ConfigHelper::setForTest('app.global_route', [
        'register_webman_route' => true,
        'openapi_doc' => [
            'scan_path' => [fixture_get_path('Swagger/ControllerForSwagger')],
        ],
    ]);

    Route::clear();
    $route = Route::getCurrent();

    Swagger::create()->registerGlobalRoute();

    $data = [];
    foreach ($route->getRoutes() as $route) {
        $data[] = [
            'path' => $route->getPath(),
            'methods' => $route->getMethods(),
        ];
    }
    $data = collect($data)->sortBy(fn(array $item) => $item['path'])->values()->toArray(); // 排个序，防止顺序问题
    expect($data)->toMatchSnapshot();

    ConfigHelper::setForTest();
});

test('ConfigRegisterRouteDTO middlewares default includes HostForbidden and BasicAuth', function () {
    $config = ConfigRegisterRouteDTO::fromConfig([]);

    expect($config->middlewares)->toHaveCount(2);
    expect($config->middlewares[0])->toBeInstanceOf(HostForbiddenMiddleware::class);
    expect($config->middlewares[1])->toBeInstanceOf(BasicAuthMiddleware::class);
});

test('ConfigRegisterRouteDTO middlewares merges with custom middlewares', function () {
    $customMiddleware = new class {};

    $config = ConfigRegisterRouteDTO::fromConfig([
        'middlewares' => [$customMiddleware],
    ]);

    expect($config->middlewares)->toHaveCount(3);
    expect($config->middlewares[0])->toBeInstanceOf(HostForbiddenMiddleware::class);
    expect($config->middlewares[1])->toBeInstanceOf(BasicAuthMiddleware::class);
    expect($config->middlewares[2])->toBe($customMiddleware);
});

test('ConfigRegisterRouteDTO basic_auth config passed to BasicAuthMiddleware', function () {
    $config = ConfigRegisterRouteDTO::fromConfig([
        'basic_auth' => [
            'enable' => true,
            'username' => 'admin',
            'password' => 'secret',
        ],
    ]);

    $basicAuthMiddleware = array_filter($config->middlewares, fn($m) => $m instanceof BasicAuthMiddleware);
    expect($basicAuthMiddleware)->toHaveCount(1);

    // 验证 basic_auth 配置生效
    expect($config->basic_auth->enable)->toBeTrue();
    expect($config->basic_auth->username)->toBe('admin');
    expect($config->basic_auth->password)->toBe('secret');
});

test('ConfigRegisterRouteDTO middlewares applied to registered routes', function () {
    $customMiddleware = new class {};
    ConfigHelper::setForTest('app.global_route', [
        'middlewares' => [$customMiddleware],
        'openapi_doc' => [
            'scan_path' => [fixture_get_path('Swagger/ControllerForSwagger')],
        ],
    ]);

    Route::clear();
    $route = Route::getCurrent();

    Swagger::create()->registerGlobalRoute();

    // 验证 swagger UI 路由包含了所有中间件
    $swaggerUiRoute = null;
    foreach ($route->getRoutes() as $r) {
        if ($r->getPath() === '/openapi') {
            $swaggerUiRoute = $r;
            break;
        }
    }
    expect($swaggerUiRoute)->not->toBeNull();
    $middlewares = $swaggerUiRoute->getMiddlewares();
    expect($middlewares)->toHaveCount(3);
    expect($middlewares[0])->toBeInstanceOf(HostForbiddenMiddleware::class);
    expect($middlewares[1])->toBeInstanceOf(BasicAuthMiddleware::class);
    expect($middlewares[2])->toBe($customMiddleware);

    ConfigHelper::setForTest();
});
