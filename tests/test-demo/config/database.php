<?php

return [
    // 默认数据库
    'default' => 'memory',

    // 各种数据库配置
    'connections' => [
        'memory' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ],
];
