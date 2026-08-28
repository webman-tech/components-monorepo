<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 纯单元测试示范（laravel 骨架 tests/Unit/ExampleTest.php 的对应物）。
 *
 * Unit 目录规范：
 * - 不启动 webman server（不绑定 Tests\TestCase，无 $this->getJson() 等 HTTP 方法）
 * - 只测纯逻辑（工具函数、纯类），跑得快；涉及真实进程/HTTP 的测试放 Feature/
 */
class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
