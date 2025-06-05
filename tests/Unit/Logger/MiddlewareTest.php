<?php

use Webman\Http\Response;
use WebmanTech\Logger\Middleware\RequestUid;

test('RequestUid', function () {
    $request = request_create_one();

    expect($request->{RequestUid::REQUEST_UID_KEY})->toBeNull();

    $middleware = new RequestUid();
    $middleware->process($request, fn() => new Response());

    expect($request->{RequestUid::REQUEST_UID_KEY})->not->toBeNull();
});
