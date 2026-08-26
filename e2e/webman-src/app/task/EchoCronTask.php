<?php

declare(strict_types=1);

namespace app\task;

use WebmanTech\CrontabTask\BaseTask;

/**
 * e2e 专用定时任务：每次执行往 runtime 追加一行，作为 cron 真实调度与 exec 命令的副作用证据
 */
class EchoCronTask extends BaseTask
{
    public function handle(): void
    {
        file_put_contents(
            runtime_path() . '/crontab-task-e2e.log',
            date('Y-m-d H:i:s') . "\n",
            FILE_APPEND,
        );
    }
}
