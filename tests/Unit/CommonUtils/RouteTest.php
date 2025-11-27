<?php

use Webman\Route\Route as WebmanRouteObject;
use WebmanTech\CommonUtils\Constants;
use WebmanTech\CommonUtils\Route;
use WebmanTech\CommonUtils\Runtime;
use WebmanTech\CommonUtils\RuntimeCustomRegister;
use WebmanTech\CommonUtils\Testing\TestRoute;

test('normalize usage', function () {
    $route = Route::getCurrent();
    expect($route->getRaw())->toBeInstanceOf(TestRoute::class);

    $callable = static function () {
        return 'ok';
    };

    // 测试基本功能
    $route->addRoute(new Route\RouteObject('GET', '/users1', $callable));
    $routeList = $route->getRoutes();
    expect($routeList)->toHaveCount(1)
        ->and($routeList[0]->getMethods())->toBe(['GET'])
        ->and($routeList[0]->getPath())->toBe('/users1')
        ->and($routeList[0]->getCallback())->toBe($callable)
        ->and($routeList[0]->getName())->toBeNull()
        ->and($routeList[0]->getMiddlewares())->toBeNull();

    // 测试 name
    $route->addRoute(new Route\RouteObject('GET', '/users2', $callable, name: 'users.index'));
    $routeItem = $route->getRouteByName('users.index');
    expect($routeItem)->toBeInstanceOf(Route\RouteObject::class)
        ->and($routeItem->getMethods())->toBe(['GET'])
        ->and($routeItem->getPath())->toBe('/users2')
        ->and($routeItem->getCallback())->toBe($callable)
        ->and($routeItem->getName())->toBe('users.index');

    // 测试 middlewares
    $route->addRoute(new Route\RouteObject('GET', '/product1', $callable, name: 'product.index', middlewares: ['auth']));
    $routeItem = $route->getRouteByName('product.index');
    expect($routeItem->getMiddlewares())->toBe(['auth']);

    // 测试多 methods 和 methods 大小写
    $route->addRoute(new Route\RouteObject(['GET', 'post'], '/product2', $callable, name: 'product2.index'));
    $routeItem = $route->getRouteByName('product2.index');
    expect($routeItem->getMethods())->toBe(['GET', 'POST']);

    // 测试 register
    $route->register([
        new Route\RouteObject('GET', '/product3', $callable, name: 'product3.index'),
    ]);
    $routeItem = $route->getRouteByName('product3.index');
    expect($routeItem->getPath())->toBe('/product3');
});

test('webman adapter', function () {
    // 保留原始的
    $runtime = Runtime::getCurrent();
    Runtime::changeRuntime(Constants::RUNTIME_WEBMAN);
    $registeredRoute = RuntimeCustomRegister::getRegistered(RuntimeCustomRegister::KEY_ROUTE);
    RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_ROUTE, null);

    $route = Route::getCurrent();
    expect($route->getRaw())->toBeNull(); // webman 没有这个实例

    $callable = static function () {
        return 'ok';
    };

    $route->addRoute(new Route\RouteObject('GET', '/users1', $callable, name: 'users.index', middlewares: ['auth']));
    $routeItem = $route->getRouteByName('users.index');
    expect($routeItem->getFrom())->toBeInstanceOf(WebmanRouteObject::class);

    // 恢复原始的
    Runtime::changeRuntime($runtime);
    RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_ROUTE, $registeredRoute);
});
