<?php

use Tests\Fixtures\Auth\Models\User;
use Tests\Fixtures\Auth\OptionalAuthentication;
use WebmanTech\Auth\Auth;
use WebmanTech\Auth\Authentication\Method\RequestMethod;
use WebmanTech\Auth\Authentication\Method\SessionMethod;
use WebmanTech\Auth\Guard\Guard;
use WebmanTech\Auth\Middleware\Authentication;
use WebmanTech\Auth\Middleware\SetAuthGuard;
use WebmanTech\CommonUtils\Response;

test('SetAuthGuard', function () {
    $request = request_create_one();

    // 默认的 guard
    expect(Auth::guard())->getAuthenticationMethod()->toBeInstanceOf(SessionMethod::class);

    $middleware = new SetAuthGuard('example_use_api_token');
    $middleware->process($request, fn() => Response::make());

    // 被 middleware 更改 guard 后
    expect(Auth::guard())->getAuthenticationMethod()->toBeInstanceOf(RequestMethod::class);
});

test('Authentication', function () {
    $request = request_create_one();

    // 初始未登录
    expect(Auth::guard()->getId())->toBeNull();

    // 无登录信息时，认证失败
    $middleware = new Authentication();
    $response = $middleware->process($request, fn() => Response::make());
    expect($response->getStatusCode())->toBe(302);

    // 模拟登录 request
    $request->getSession()->set(Guard::SESSION_AUTH_ID, User::MOCK_TOKEN);

    $middleware = new Authentication();
    $middleware->process($request, fn() => Response::make());

    // 登录成功
    expect(Auth::guard()->getId())->toBe(User::MOCK_ID);
});

test('Authentication 可选的路由', function () {
    $request = request_create_one();
    $originalRequest = request_get_original($request);
    $middleware = new OptionalAuthentication();

    // 初始未登录
    expect(Auth::guard()->getId())->toBeNull();

    // 无登录信息时，认证失败
    $response = $middleware->process($request, fn() => Response::make());
    expect($response->getStatusCode())->toBe(302);

    // 使用可选的路由，认证通过
    $originalRequest->setGet('optional', 'true');
    $response = $middleware->process($request, fn() => Response::make());
    expect($response->getStatusCode())->toBe(200);

    // 如果设置认证信息，后续也能获取到
    $request->getSession()->set(Guard::SESSION_AUTH_ID, User::MOCK_TOKEN);
    $middleware = new Authentication();
    $middleware->process($request, fn() => Response::make());
    expect(Auth::guard()->getId())->toBe(User::MOCK_ID);
});
