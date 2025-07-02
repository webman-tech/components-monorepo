<?php

use Tests\Fixtures\ClearableWebmanRoute;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Swagger;

test('registerGlobalRoute dont regsiter_webman_route', function () {
    ConfigHelper::setForTest('app.global_route', [
        'register_webman_route' => false,
    ]);

    Swagger::create()->registerGlobalRoute();

    $data = [];
    foreach (ClearableWebmanRoute::getRoutes() as $route) {
        $data[] = [
            'path' => $route->getPath(),
            'methods' => $route->getMethods(),
        ];
    }
    expect($data)->toMatchSnapshot();

    ConfigHelper::setForTest();
    ClearableWebmanRoute::clean();
});

test('registerGlobalRoute register_webman_route', function () {
    ConfigHelper::setForTest('app.global_route', [
        'register_webman_route' => true,
        'openapi_doc' => [
            'scan_path' => [fixture_get_path('Swagger/ControllerForSwagger')],
        ],
    ]);

    Swagger::create()->registerGlobalRoute();

    $data = [];
    foreach (ClearableWebmanRoute::getRoutes() as $route) {
        $data[] = [
            'path' => $route->getPath(),
            'methods' => $route->getMethods(),
        ];
    }
    expect($data)->toMatchSnapshot();

    ConfigHelper::setForTest();
    ClearableWebmanRoute::clean();
});
