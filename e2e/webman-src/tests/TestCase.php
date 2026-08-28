<?php

namespace Tests;

use support\Db;
use WebmanTech\Testing\TestCase as BaseTestCase;

/**
 * 应用级测试基类（laravel 骨架 tests/TestCase.php 的对应物）
 *
 * pest 经 tests/Pest.php 的 pest()->extend(TestCase::class)->in('Feature') 绑定后闭包内直接 $this->xxx。
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 先确保 webman 配置已加载（Server::instance 触发 ensureConfigLoaded），support\Db 才能读到 config('database')
        $this->webmanServer();
        // Illuminate sqlite 连接要求文件已存在（原生 PDO 会自动创建）；建表由 server 进程完成
        $dbFile = $this->webmanRuntimePath('e2e.sqlite');
        if (!is_file($dbFile)) {
            touch($dbFile);
        }
        // 数据库直连断言：support\Db 与 server 进程读同一份 config/database.php（phpunit.xml 注入 DB_CONNECTION=sqlite）
        $pdo = Db::connection()->getPdo();
        // sqlite 文件库与 server 进程并发写时等待锁释放而非立即报错
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $this->setDatabaseConnection($pdo);
    }
}
