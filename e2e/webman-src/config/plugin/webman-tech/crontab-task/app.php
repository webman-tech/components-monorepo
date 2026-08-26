<?php

return [
    'enable' => true,
    // 覆盖插件默认的 null（不记录日志）：写入骨架 default channel，验证 LogTrait 日志链路
    'log' => [
        'channel' => 'default',
    ],
];
