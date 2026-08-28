<?php

// 注意：workerman/crontab 的调度按整分钟对齐（new Crontab() 后等到下一个 xx:00 才首次触发），
// 涉及时序的等待均通过 $this->webmanWaitFor() 且 timeout 覆盖跨分钟边界。

use app\task\EchoCronTask;

test('定时任务进程随 server 启动并按 cron 调度执行', function () {
    $this->webmanServer()->ensureStarted();

    $runtimePath = $this->webmanRuntimePath();
    $initial = e2e_crontab_task_count($runtimePath);

    // cron 为 */1 * * * * *（每秒），跨过整分钟后必然增长
    $current = $this->webmanWaitFor(function () use ($initial, $runtimePath) {
        clearstatcache();
        $count = e2e_crontab_task_count($runtimePath);

        return $count > $initial ? $count : false;
    }, 60 - (int)date('s') + 10, 0.3);

    expect($current)->toBeGreaterThan($initial);
});

test('任务执行日志经 LogTrait 写入配置的 channel', function () {
    // 先确保任务真实执行过（覆盖跨分钟等待）
    $this->webmanWaitFor(function () {
        return e2e_crontab_task_count($this->webmanRuntimePath()) > 0;
    }, 60 - (int)date('s') + 10, 0.3);

    // default channel（RotatingFileHandler）的实际文件名为 webman-{date}.log
    $logFile = $this->webmanRuntimePath('logs/webman-' . date('Y-m-d') . '.log');

    // 副作用行写入时 start/end 可能尚未全部落盘（同一执行内：start → 副作用行 → end），轮询等待
    $content = $this->webmanWaitFor(function () use ($logFile) {
        $content = is_file($logFile) ? (string)file_get_contents($logFile) : '';

        return str_contains($content, EchoCronTask::class . ':start')
            && str_contains($content, EchoCronTask::class . ':end') ? $content : false;
    }, 5, 0.2);

    // LogTrait 默认 log_class=true，消息格式为 {taskClass}:{msg}
    expect($content)->toContain(EchoCronTask::class . ':start')
        ->and($content)->toContain(EchoCronTask::class . ':end');
});

test('crontab-task:list 展示已注册的定时任务', function () {
    $this->webmanCommand('crontab-task:list')
        ->assertSuccessful()
        ->assertOutputContains(EchoCronTask::class)
        ->assertOutputContains('*/1 * * * * *')
        ->assertOutputContains('cron_task_e2e');
});

test('crontab-task:exec 手动执行指定任务', function () {
    $before = e2e_crontab_task_count($this->webmanRuntimePath());

    $this->webmanCommand('crontab-task:exec', EchoCronTask::class)->assertSuccessful();

    // exec 在 CLI 进程内同步执行一次（副作用 +1）；
    // server 存活期间 cron 调度可能并发追加，故用 >= 断言
    expect(e2e_crontab_task_count($this->webmanRuntimePath()))->toBeGreaterThanOrEqual($before + 1);
});
