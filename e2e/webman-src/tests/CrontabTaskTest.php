<?php

// crontab-task 在真实 webman 进程环境下的行为验证：
// 插件 process 配置被加载 → TaskProcess 进程随 server 启动 → cron 调度真实触发 BaseTask 全链路（日志）
//
// 注意：workerman/crontab 的调度按整分钟对齐（new Crontab() 后等到下一个 xx:00 才首次触发），
// 涉及时序的断言均通过 e2e_crontab_task_wait_executed() 覆盖跨分钟边界。

use app\task\EchoCronTask;
use Symfony\Component\Process\Process;

test('定时任务进程随 server 启动并按 cron 调度执行', function () {
    e2e_server()->ensureStarted();

    $initial = e2e_crontab_task_count();
    $current = e2e_crontab_task_wait_executed($initial);

    // cron 为 */1 * * * * *（每秒），跨过整分钟后必然增长
    expect($current)->toBeGreaterThan($initial);
});

test('任务执行日志经 LogTrait 写入配置的 channel', function () {
    // 先确保任务真实执行过（覆盖跨分钟等待）
    e2e_crontab_task_wait_executed();

    // default channel（RotatingFileHandler）的实际文件名为 webman-{date}.log
    $logFile = e2e_server()->appDir() . '/runtime/logs/webman-' . date('Y-m-d') . '.log';

    // 副作用行写入时 start/end 可能尚未全部落盘（同一执行内：start → 副作用行 → end），轮询等待
    $deadline = microtime(true) + 5;
    do {
        $content = is_file($logFile) ? (string)file_get_contents($logFile) : '';
        $done = str_contains($content, EchoCronTask::class . ':start')
            && str_contains($content, EchoCronTask::class . ':end');
        if ($done) {
            break;
        }
        usleep(200_000);
    } while (microtime(true) < $deadline);

    // LogTrait 默认 log_class=true，消息格式为 {taskClass}:{msg}
    expect($content)->toContain(EchoCronTask::class . ':start')
        ->and($content)->toContain(EchoCronTask::class . ':end');
});

test('crontab-task:list 展示已注册的定时任务', function () {
    $process = new Process(
        [PHP_BINARY, 'webman', 'crontab-task:list'],
        e2e_server()->appDir(),
        null,
        null,
        60,
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain(EchoCronTask::class)
        ->and($process->getOutput())->toContain('*/1 * * * * *')
        ->and($process->getOutput())->toContain('cron_task_e2e');
});

test('crontab-task:exec 手动执行指定任务', function () {
    $before = e2e_crontab_task_count();

    $process = new Process(
        [PHP_BINARY, 'webman', 'crontab-task:exec', EchoCronTask::class],
        e2e_server()->appDir(),
        null,
        null,
        60,
    );
    $process->run();

    // exec 在 CLI 进程内同步执行一次（副作用 +1）；
    // server 存活期间 cron 调度可能并发追加，故用 >= 断言
    expect($process->getExitCode())->toBe(0)
        ->and(e2e_crontab_task_count())->toBeGreaterThanOrEqual($before + 1);
});
