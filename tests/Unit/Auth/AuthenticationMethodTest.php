<?php

use Tests\Fixtures\Auth\Models\User;
use Webman\Http\Request;
use WebmanTech\Auth\Authentication\Method\CompositeMethod;
use WebmanTech\Auth\Authentication\Method\HttpAuthorizationMethod;
use WebmanTech\Auth\Authentication\Method\HttpBasicMethod;
use WebmanTech\Auth\Authentication\Method\HttpBearerMethod;
use WebmanTech\Auth\Authentication\Method\HttpHeaderMethod;
use WebmanTech\Auth\Authentication\Method\RequestMethod;
use WebmanTech\Auth\Authentication\Method\SessionMethod;
use WebmanTech\Auth\Guard\Guard;

describe('简单的认证方式', function () {
    $cases = [
        [
            'method' => new HttpAuthorizationMethod(new User()),
            'request_mock' => fn(Request $request) => $request->setHeader('authorization', User::MOCK_TOKEN)
        ],
        [
            'method' => new HttpBearerMethod(new User()),
            'request_mock' => fn(Request $request) => $request->setHeader('authorization', 'Bearer ' . User::MOCK_TOKEN),
        ],
        [
            'method' => new HttpHeaderMethod(new User()),
            'request_mock' => fn(Request $request) => $request->setHeader('x-api-key', User::MOCK_TOKEN),
        ],
        [
            'method' => new RequestMethod(new User()),
            'request_mock' => fn(Request $request) => $request->setPost('access-token', User::MOCK_TOKEN),
        ],
        [
            'method' => new SessionMethod(new User()),
            'request_mock' => fn(Request $request) => $request->session()->set(Guard::SESSION_AUTH_ID, User::MOCK_TOKEN),
        ],
    ];

    foreach ($cases as $case) {
        $method = $case['method'];
        test($method::class, function () use ($method, $case) {
            $request = request_create_one();
            // 获取不到信息时认证失败
            $identity = $method->authenticate($request);
            expect($identity)->toBeNull();
            // 模拟能够获取到信息
            $case['request_mock']($request);
            // 能够正常认证
            $identity = $method->authenticate($request);
            expect($identity)->toBeInstanceOf(User::class);
        });
    }
});

test('组合认证方式', function () {
    $method = new CompositeMethod([
        new RequestMethod(new User()),
        new HttpHeaderMethod(new User()),
    ]);
    // 获取不到信息时认证失败
    $request = request_create_one();
    $identity = $method->authenticate($request);
    expect($identity)->toBeNull();
    // 模拟能够获取到信息
    $request->setHeader('x-api-key', User::MOCK_TOKEN);
    // 能够正常认证
    $identity = $method->authenticate($request);
    expect($identity)->toBeInstanceOf(User::class);

    // 重建一个 request 模拟另一种认证
    $request = request_create_one();
    $request->setPost('access-token', User::MOCK_TOKEN);
    // 能够正常认证
    $identity = $method->authenticate($request);
    expect($identity)->toBeInstanceOf(User::class);
});

test('Basic 认证', function () {
    $method = new HttpBasicMethod(new User());
    // 获取不到信息时认证失败
    $request = request_create_one();
    $identity = $method->authenticate($request);
    expect($identity)->toBeNull();
    // 非标准的 basic 信息，能够正常认证
    $request->setHeader('authorization', 'Basic ' . User::MOCK_TOKEN);
    $identity = $method->authenticate($request);
    expect($identity)->toBeNull();
    // 标准的 basic 信息，能够正常认证
    $request->setHeader('authorization', 'Basic ' . base64_encode(User::MOCK_TOKEN_BASIC));
    $identity = $method->authenticate($request);
    expect($identity)->toBeInstanceOf(User::class);
});
