<?php

/**
 * e2e：sqlite :memory: 最小数据库配置
 *
 * webman-tech/laravel-monorepo 的 validator() 依赖 support\Db 实例
 * （DatabasePresenceVerifier），而 Webman\Database\Initializer 仅在
 * connections 非空时才会创建 Capsule 实例，缺少本文件会导致
 * "Call to a member function getDatabaseManager() on null"。
 */
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ],
];
