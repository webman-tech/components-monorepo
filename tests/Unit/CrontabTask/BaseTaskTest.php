<?php

use Tests\Fixtures\CrontabTask\EmptyTask;
use Tests\Fixtures\CrontabTask\ExceptionTask;
use Tests\Fixtures\CrontabTask\MultiEventTask;
use Tests\Fixtures\CrontabTask\SimpleTask;

afterAll(function () {
    // 由于 webman 进程不重启不会重新去创建日志文件，因此在全部 test 跑完后再删
    $logFile = runtime_path() . '/logs/task.log';
    if (file_exists($logFile)) {
        unlink($logFile);
    }
});

test('taskExec 执行正常', function () {
    expect(SimpleTask::$counter)->toBe(0);
    SimpleTask::taskExec();
    expect(SimpleTask::$counter)->toBe(1);
    SimpleTask::taskExec();
    expect(SimpleTask::$counter)->toBe(2);
});

test('task log', function () {
    $logFile = runtime_path() . '/logs/task.log';
    if (file_exists($logFile)) {
        file_put_contents($logFile, ''); // 清空内容
    }

    EmptyTask::taskExec();
    expect(file_exists($logFile))->toBeTrue();
    $content = file_get_contents($logFile);
    expect($content)->toContain(EmptyTask::class); // 有 class 关键词

    SimpleTask::taskExec();
    $content = file_get_contents($logFile);
    expect($content)->toContain(SimpleTask::class); // 有 class 关键词
});

test('taskExec 执行有异常的情况', function () {
    $logFile = runtime_path() . '/logs/task.log';
    if (file_exists($logFile)) {
        file_put_contents($logFile, ''); // 清空内容
    }

    ExceptionTask::taskExec();
    expect(file_exists($logFile))->toBeTrue();
    $content = file_get_contents($logFile);
    expect($content)->toContain('Task Exception', 'WARNING'); // TaskException 记录 warning

    ExceptionTask::$useTaskException = false;
    ExceptionTask::taskExec();
    $content = file_get_contents($logFile);
    expect($content)->toContain('Another Exception', 'ERROR'); // 其他记录 ERROR
})->depends('task log');

test('task event', function () {
    SimpleTask::$markArr = [];
    SimpleTask::taskExec();
    expect(SimpleTask::$markArr)->toBe([
        'before_exec',
        'after_exec',
    ]);

    MultiEventTask::$markArr = [];
    MultiEventTask::taskExec();
    expect(SimpleTask::$markArr)->toBe([
        'before_exec',
        'before',
        'after_exec',
        'after',
    ]);
});
