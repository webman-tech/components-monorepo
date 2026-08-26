<?php

use WebmanTech\CrontabTask\Schedule;

return (new Schedule())
    // 每秒执行一次，e2e 轮询断言副作用文件增长（无需等待分钟级 cron）
    ->addTask('e2e', '*/1 * * * * *', \app\task\EchoCronTask::class)
    ->buildProcesses();
