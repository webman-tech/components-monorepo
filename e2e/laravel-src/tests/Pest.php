<?php

/*

|--------------------------------------------------------------------------
| e2e Pest 配置
|--------------------------------------------------------------------------
| 覆盖骨架默认（骨架为 PHPUnit 风格），统一使用 Pest 风格的 Feature 测试。
| 依赖 pestphp/pest-plugin-laravel（由 e2e/setup.php 补充安装）。
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');
