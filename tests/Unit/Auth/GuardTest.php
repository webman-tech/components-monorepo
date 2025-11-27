<?php

use Tests\Fixtures\Auth\Models\User;
use WebmanTech\Auth\Authentication\FailureHandler\ResponseHandler;
use WebmanTech\Auth\Authentication\Method\RequestMethod;
use WebmanTech\Auth\Guard\Guard;
use WebmanTech\Auth\Interfaces\AuthenticationMethodInterface;
use WebmanTech\Auth\Interfaces\IdentityRepositoryInterface;

test('Guard', function () {
    $guard = new Guard([
        'identityRepository' => fn() => new User(),
        'authenticationMethod' => fn(IdentityRepositoryInterface $identityRepository) => new RequestMethod($identityRepository),
        'authenticationFailureHandler' => fn(AuthenticationMethodInterface $authenticationMethod) => new ResponseHandler(),
        'sessionEnable' => false,
    ]);

    // 未认证前
    expect($guard->getAuthenticationMethod())->toBeInstanceOf(RequestMethod::class)
        ->and($guard->getAuthenticationFailedHandler())->toBeInstanceOf(ResponseHandler::class)
        ->and($guard->isGuest())->toBeTrue()
        ->and($guard->getUser())->toBeNull()
        ->and($guard->getId())->toBeNull();
    // 准备认证
    $request = request_create_one();
    request_get_original($request)->setPost('access-token', User::MOCK_TOKEN);
    // 认证
    $identity = $guard->getAuthenticationMethod()->authenticate($request);
    expect($identity)->toBeInstanceOf(User::class);
    // 认证后登录
    $guard->login($identity);
    expect($guard->isGuest())->toBeFalse()
        ->and($guard->getUser())->toBeInstanceOf(User::class)
        ->and($guard->getId())->toBe(User::MOCK_ID)
        ->and($request->getSession()->get(Guard::SESSION_AUTH_ID))->toBeNull(); // 未启用 session 支持
    // 退出登录
    $guard->logout();
    expect($guard->isGuest())->toBeTrue()
        ->and($guard->getUser())->toBeNull()
        ->and($guard->getId())->toBeNull();
});

test('Guard 支持 session', function () {
    $guard = new Guard([
        'identityRepository' => fn() => new User(),
        'authenticationMethod' => fn(IdentityRepositoryInterface $identityRepository) => new RequestMethod($identityRepository),
        'authenticationFailureHandler' => fn(AuthenticationMethodInterface $authenticationMethod) => new ResponseHandler(),
        'sessionEnable' => true,
    ]);

    // 准备认证
    $request = request_create_one();
    request_get_original($request)->setPost('access-token', User::MOCK_TOKEN);
    // 认证
    $identity = $guard->getAuthenticationMethod()->authenticate($request);
    expect($identity)->toBeInstanceOf(User::class);
    // 认证后登录
    $guard->login($identity);
    expect($guard->isGuest())->toBeFalse()
        ->and($request->getSession()->get(Guard::SESSION_AUTH_ID))->toBe(User::MOCK_ID); // 可以从 session 中获取 id
    // 退出登录
    $guard->logout();
    expect($guard->isGuest())->toBeTrue()
        ->and($request->getSession()->get(Guard::SESSION_AUTH_ID))->toBeNull(); // 退出登录后同步清除
});
