<?php

use WebmanTech\CommonUtils\Response;
use WebmanTech\Logger\Middleware\RequestTraceMiddleware;

test('RequestTraceMiddleware', function () {
    $request = request_create_one();

    expect($request->getCustomData(RequestTraceMiddleware::KEY_TRACE_ID))->toBeNull();

    $middleware = new RequestTraceMiddleware();
    $middleware->process($request, fn() => Response::make());

    expect($request->getCustomData(RequestTraceMiddleware::KEY_TRACE_ID))->not->toBeNull();
});
