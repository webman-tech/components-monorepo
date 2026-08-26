<?php

// logger 包在真实 laravel 环境下的中间件与 channel 行为

test('HttpRequestLogMiddleware 将请求写入 httpRequest channel 日志', function () {
    $this->get('/e2e/echo?q=log-test')->assertOk();

    $logFile = storage_path('logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log');
    expect($logFile)->toBeFile();

    $content = (string)file_get_contents($logFile);
    expect($content)->toContain('GET:/e2e/echo')
        ->and($content)->toContain('127.0.0.1');
});

test('日志包含 RequestTraceProcessor 生成的 traceId', function () {
    $this->get('/e2e/echo?q=trace-test')->assertOk();

    $logFile = storage_path('logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log');
    $content = (string)file_get_contents($logFile);

    // ChannelFormatter 格式：[%datetime%][%extra.traceId%][%level_name%][%extra.ip%]...
    expect($content)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+\]\[trace[a-f0-9]+\]\[INFO\]/');

    // RequestTraceMiddleware 将 traceId 写入 request custom data，同一请求的日志应复用同一 traceId
    preg_match_all('/\[trace[a-f0-9]+/', $content, $matches);
    expect($matches[0])->not->toBeEmpty();
});

test('Log facade 的 laravel 分支可写包配置的 channel', function () {
    // /e2e/log 路由内通过 WebmanTech\CommonUtils\Log::channel('httpRequest') 写入
    $this->get('/e2e/log')->assertOk();

    $logFile = storage_path('logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log');
    expect((string)file_get_contents($logFile))->toContain('e2e log message');
});
