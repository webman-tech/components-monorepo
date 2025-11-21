<?php

use Tests\Fixtures\CrontabTask\SimpleTask;
use WebmanTech\CrontabTask\BaseTask;

return [
    'enable' => true,
    'log' => [
        /**
         * @see \WebmanTech\CrontabTask\Traits\LogTrait::log()
         */
        'channel' => 'task', // 为 null 时不记录日志
    ],
    'event' => [
        'before_exec' => function (BaseTask $task) {
            if ($task instanceof SimpleTask) {
                $task->mark('before_exec');
            }
        },
        'after_exec' => function (BaseTask $task) {
            if ($task instanceof SimpleTask) {
                $task->mark('after_exec');
            }
        },
    ],
];
