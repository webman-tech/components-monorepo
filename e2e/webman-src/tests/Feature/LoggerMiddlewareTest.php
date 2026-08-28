<?php

test('HttpRequestLogMiddleware 将请求写入 httpRequest channel 日志', function () {
    $this->get('/echo/get?foo=log-check')->assertOk();

    // SplitMode + RotatingFileHandler 的实际文件名为 {channel}-{date}.log
    $logFile = $this->webmanRuntimePath('logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log');
    expect($logFile)->toBeFile();

    $content = (string)file_get_contents($logFile);
    expect($content)->toContain('GET:/echo/get')
        ->and($content)->toContain('foo=log-check');
});

test('日志包含 RequestTraceProcessor 生成的 traceId', function () {
    $traceId = $this->getJson('/echo/session')->json('traceId');

    // ResetLog 中间件会 close 句柄，稍等日志落盘
    usleep(100_000);

    $logFile = $this->webmanRuntimePath('logs/httpRequest/httpRequest-' . date('Y-m-d') . '.log');
    $content = (string)file_get_contents($logFile);

    expect($content)->toContain($traceId);
});
