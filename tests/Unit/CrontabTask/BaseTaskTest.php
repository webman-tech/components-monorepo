<?php

use Tests\Fixtures\CrontabTask\EmptyTask;
use Tests\Fixtures\CrontabTask\ExceptionTask;
use Tests\Fixtures\CrontabTask\MultiEventTask;
use Tests\Fixtures\CrontabTask\SimpleTask;
use WebmanTech\CommonUtils\Testing\TestLogger;

beforeEach(function () {
    $this->logger = TestLogger::channel('task');
});

test('taskExec 执行正常', function () {
    expect(SimpleTask::$counter)->toBe(0);
    SimpleTask::taskExec();
    expect(SimpleTask::$counter)->toBe(1);
    SimpleTask::taskExec();
    expect(SimpleTask::$counter)->toBe(2);
});

test('task log', function () {
    $logger = TestLogger::channel('task');
    $logger->flush();

    EmptyTask::taskExec();
    expect($logger->getAllString())->toContain(EmptyTask::class); // 有 class 关键词

    SimpleTask::taskExec();
    expect($logger->getAllString())->toContain(SimpleTask::class); // 有 class 关键词
});

test('taskExec 执行有异常的情况', function () {
    $logger = TestLogger::channel('task');
    $logger->flush();

    ExceptionTask::taskExec();
    expect($logger->getAllString())->toContain('Task Exception', 'WARNING'); // TaskException 记录 warning

    ExceptionTask::$useTaskException = false;
    ExceptionTask::taskExec();
    expect($logger->getAllString())->toContain('Another Exception', 'ERROR'); // 其他记录 ERROR
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
