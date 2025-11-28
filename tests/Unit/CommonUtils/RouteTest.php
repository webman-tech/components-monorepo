<?php

use Tests\Fixtures\CommonUtils\RouteController;
use WebmanTech\CommonUtils\Constants;
use WebmanTech\CommonUtils\Route;
use WebmanTech\CommonUtils\Runtime;
use WebmanTech\CommonUtils\RuntimeCustomRegister;
use WebmanTech\CommonUtils\Testing\TestRoute;
use WebmanTech\CommonUtils\Testing\Webman\ClearableRoute;

describe('different adapter test', function () {
    $cachedData = [];
    $cases = [
        [
            'title' => 'custom route',
            'instance_class' => TestRoute::class,
            'get_route' => function () {
                Route::clear();
                return Route::getCurrent();
            },
            'extra_asserts' => function (Route $route) {
                $routeObject = $route->getRouteByName('users.detail');
                expect($routeObject->getUrl())->toBeNull()
                    ->and($routeObject->getFrom())->toBeNull();
            },
        ],
        [
            'title' => 'webman route',
            'instance_class' => null,
            'get_route' => function () use (&$cachedData) {
                $cachedData['runtime'] = Runtime::getCurrent();
                $cachedData['route'] = RuntimeCustomRegister::getRegistered(RuntimeCustomRegister::KEY_ROUTE);
                Runtime::changeRuntime(Constants::RUNTIME_WEBMAN);
                RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_ROUTE, null);

                Route::clear();
                return Route::getCurrent();
            },
            'extra_asserts' => function (Route $route) {
                $request = request_create_one();
                $raw = request_get_raw($request);
                $raw->setHeader('x-forwarded-prefix', '/app');

                $routeObject = $route->getRouteByName('users.detail');
                expect($routeObject->getUrl(['id' => 1]))->toBe('/users/1')
                    ->and($routeObject->getUrl(['id' => 1], appendPrefix: true))->toBe('/app/users/1')
                    ->and($routeObject->getFrom())->toBeInstanceOf(\Webman\Route\Route::class);
            },
            'cleanup' => function () use (&$cachedData) {
                Runtime::changeRuntime($cachedData['runtime']);
                RuntimeCustomRegister::register(RuntimeCustomRegister::KEY_ROUTE, $cachedData['route']);
                ClearableRoute::clear();
                unset($cachedData);
            },
        ],
    ];

    foreach ($cases as $case) {
        test($case['title'], function () use ($case) {
            $callable = static fn() => 'ok';

            /** @var Route $route */
            $route = $case['get_route']();

            // 检查 raw 类型
            if (class_exists($case['instance_class'])) {
                expect($route->getRaw())->toBeInstanceOf($case['instance_class']);
            } else {
                expect($route->getRaw())->toBeNull();
            }

            // 基本测试
            $route->addRoute(new Route\RouteObject('GET', '/demo/{id}', $callable));
            $routeList = $route->getRoutes();
            expect($routeList)->not->toBeEmpty()
                ->and($routeList[0]->getMethods())->toBe(['GET'])
                ->and($routeList[0]->getPath())->toBe('/demo/{id}')
                ->and($routeList[0]->getCallback())->toBe($callable)
                ->and(($routeList[0]->getCallback())())->toBe('ok')
                ->and($routeList[0]->getName())->toBeNull()
                ->and($routeList[0]->getMiddlewares())->toBeNull();

            // 命名测试
            $route->addRoute(new Route\RouteObject('GET', '/users/{id}', $callable, name: 'users.detail'));
            $routeItem = $route->getRouteByName('users.detail');
            expect($routeItem)->toBeInstanceOf(Route\RouteObject::class)
                ->and($routeItem->getPath())->toBe('/users/{id}')
                ->and($routeItem->getName())->toBe('users.detail');

            // middleware 测试
            $route->addRoute(new Route\RouteObject('GET', '/product1', $callable, name: 'product.index', middlewares: ['auth']));
            expect($route->getRouteByName('product.index')->getMiddlewares())->toBe(['auth']);

            // 多 methods 和 大小写测试
            $route->addRoute(new Route\RouteObject(['GET', 'post'], '/product2', $callable, name: 'product2.index'));
            expect($route->getRouteByName('product2.index')->getMethods())->toBe(['GET', 'POST']);

            // callable 测试
            $controllerCallable = [RouteController::class, 'index'];
            $route->addRoute(new Route\RouteObject('GET', '/product3', $controllerCallable, name: 'product3.index'));
            $controllerRoute = $route->getRouteByName('product3.index');
            expect($controllerRoute->getCallback())->toBe($controllerCallable)
                ->and(($controllerRoute->getCallback())())->toBe('abc');

            // 额外测试
            if (isset($case['extra_asserts'])) {
                $case['extra_asserts']($route);
            }

            // 清理
            if (isset($case['cleanup'])) {
                $case['cleanup']();
            }
        });
    }
});
