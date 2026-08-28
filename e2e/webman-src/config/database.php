<?php

/**
 * 数据库配置：default 经 env 切换（phpunit.xml 注入 DB_CONNECTION=sqlite；业务运行时无 env 回退 mysql）
 *
 * 使用文件型 sqlite（而非 :memory:）：:memory: 库仅存在于 server 进程内，测试进程无法跨进程连接；
 * 测试进程与 server 进程经 support\Db 读同一份配置（runtime_path() 同源定位同一文件）。
 */
return [
    'default' => getenv('DB_CONNECTION') ?: 'mysql',
    'connections' => [
        // 业务连接（e2e 无真实 mysql，仅为业务形态示范）
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_DATABASE') ?: 'e2e',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => runtime_path('e2e.sqlite'),
            'prefix' => '',
        ],
    ],
];
