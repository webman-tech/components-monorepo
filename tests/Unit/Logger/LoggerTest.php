<?php

use support\Log;
use Tests\Fixtures\Logger\MyLogger;

test('日志写入', function () {
    $logFile = MyLogger::getLogFile('logger_test', true);

    MyLogger::logger_test('abc');
    Log::channel('logger_test')->info('xyz');

    // 检查日志文件存在
    expect(file_exists($logFile))->toBeTrue();

    // 检查内容条数
    $content = file_get_contents($logFile);
    $data = explode("\n", trim($content));
    expect(count($data))->toBe(2);

    // 检查内容
    expect($data[0])->toContain(date('Y-m-d'), 'abc', 'INFO')
        ->and($data[1])->toContain(date('Y-m-d'), 'xyz', 'INFO');
});
