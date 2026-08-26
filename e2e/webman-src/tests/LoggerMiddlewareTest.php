<?php

// logger 包中间件在真实 webman 链路下的行为验证

test('HttpRequestLogMiddleware 将请求写入 httpRequest channel 日志', function () {
    $response = e2e_request('GET', '/echo/get?foo=log-check');
    expect($response->getStatusCode())->toBe(200);

    // SplitMode + RotatingFileHandler 的实际文件名为 {channel}-{date}.log
    $logFile = e2e_server()->appDir() . '/runtime/logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log';
    expect($logFile)->toBeFile();

    $content = (string)file_get_contents($logFile);
    expect($content)->toContain('GET:/echo/get')
        ->and($content)->toContain('foo=log-check');
});

test('日志包含 RequestTraceProcessor 生成的 traceId', function () {
    $data = e2e_json('GET', '/echo/session');
    $traceId = $data['traceId'];

    // ResetLog 中间件会 close 句柄，稍等日志落盘
    usleep(100_000);

    $logFile = e2e_server()->appDir() . '/runtime/logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log';
    $content = (string)file_get_contents($logFile);

    expect($content)->toContain($traceId);
});
