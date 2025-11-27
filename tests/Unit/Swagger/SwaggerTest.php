<?php

use WebmanTech\CommonUtils\Route;
use WebmanTech\Swagger\Helper\ConfigHelper;
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
