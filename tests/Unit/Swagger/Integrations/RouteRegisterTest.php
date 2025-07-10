<?php

use Tests\Fixtures\ClearableWebmanRoute;
use WebmanTech\Swagger\Integrations\RouteRegister;
use WebmanTech\Swagger\RouteAnnotation\Reader;

test('register route', function () {
    $reader = new Reader();
    $data = $reader->getData(fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution'));
    expect($data)->toMatchSnapshot();

    $register = RouteRegister::create();
    $register->register($data);

    $registeredRoutes = [];
    foreach (ClearableWebmanRoute::getRoutes() as $route) {
        foreach ($route->getMethods() as $method) {
            $path = $route->getPath();
            $middlewares = array_filter($route->getMiddleware(), function ($middleware) {
                return is_string($middleware); // 暂时只支持一下字符串形式的中间件，callback 的不好校验
            });
            $registeredRoutes[$method . ':' . $path] = [
                'name' => $route->getName(),
                'method' => $method,
                'path' => $path,
                'callback' => $route->getCallback(),
                'middlewares' => array_reverse($middlewares),
            ];
        }
    }

    expect($registeredRoutes)->toMatchSnapshot();

    ClearableWebmanRoute::clean();
});
