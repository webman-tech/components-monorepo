<?php

use WebmanTech\Auth\Auth;
use WebmanTech\Auth\Authentication\Method\RequestMethod;
use WebmanTech\Auth\Authentication\Method\SessionMethod;

test('Auth Facade', function () {
    // 必须先能支持 request() 才能继续后续的
    request_create_one();

    // 取默认的 guard
    $guard = Auth::guard();
    expect($guard->getAuthenticationMethod())->toBeInstanceOf(SessionMethod::class);

    // 取指定的 guard
    $guard = Auth::guard('example_use_api_token');
    expect($guard->getAuthenticationMethod())->toBeInstanceOf(RequestMethod::class);
});
