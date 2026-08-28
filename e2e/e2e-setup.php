<?php

declare(strict_types=1);

use WebmanTech\Testing\E2eSetup\Definition\AppConfig;
use WebmanTech\Testing\E2eSetup\SetupConfig;

/**
 * e2e 应用定义（rector.php 风格；相对路径基于本文件所在目录 e2e/ 解析）。
 * 安装编排由 webman-tech/testing 的 e2e-setup 框架执行（vendor/bin/e2e-setup）。
 *
 * 用法：
 *   vendor/bin/e2e-setup install webman|laravel   完整安装（create-project → patch → update → reinstall → sync）
 *   vendor/bin/e2e-setup sync webman|laravel      仅同步自有代码（dev 快速迭代）
 *   vendor/bin/e2e-setup install webman --vcs     被测包经 GitHub VCS dev-main 安装（发布链路验证，需先推送 main）
 */

// 被测组件（monorepo packages/ 下；同 path 自动合并为单条 path repository，symlink + versions 钉 dev-main）
// path 必须带 *（glob）：目录本身无 composer.json，不带 * 时 path repository 静默失效、退而装 packagist 版本
$packages = [
    'webman-tech/amis-admin',
    'webman-tech/auth',
    'webman-tech/common-utils',
    'webman-tech/crontab-task',
    'webman-tech/debugbar',
    'webman-tech/dto',
    'webman-tech/log-reader',
    'webman-tech/logger',
    'webman-tech/swagger',
];

// webman 应用：全部被测组件 + 插件 Install 链路
$webman = AppConfig::configure()
    ->skeleton('workerman/webman')
    ->targetDir('webman')
    ->srcDir('webman-src');
foreach ($packages as $name) {
    $webman->package($name, '../packages/*');
}
$webman
    ->require(array_merge(
        array_fill_keys($packages, 'dev-main'),
        [
            // 仅安装被测包声明的依赖 + 单一框架，验证各包依赖声明完整性
            'webman-tech/laravel-monorepo' => '12.x-dev', // webman 环境下提供 validator() helper
            'tinywan/jwt' => '^1.11', // auth 包的可选依赖，验证可选依赖场景
        ],
    ))
    ->requireDev([
        'pestphp/pest' => '^3.8',
        // 测试框架本身（独立仓库已发布到 packagist，dev-main 安装）
        'webman-tech/testing' => 'dev-main',
        // testing 组件的 PSR-18 HTTP 客户端（自动发现；组件本身不再强依赖 guzzle）
        'guzzlehttp/guzzle' => '^7.8',
        // TestCase 中经 support\Db 做数据库直连断言
        'webman/database' => '^2.1',
        // 加载 plugin 的 command.php，验证 crontab-task 等包的 CLI 命令集成链路
        'webman/console' => '^2.0',
    ])
    // 骨架自带 monolog ^2，与 laravel-monorepo 拉入的 illuminate 12（monolog ^3）冲突，升级为 ^3
    ->requireOverride([
        'monolog/monolog' => '^3.0',
    ])
    // 批量 composer update 时 composer 进程内 autoloader 未就绪，包内 Install.php 不触发；
    // 单包 reinstall 走真实安装链（post-package-install → support\Plugin::install）。
    // webman/console 也是 webman 插件（落地 `webman` CLI 入口 + config/plugin），需一并 reinstall。
    ->reinstallPackages(array_merge($packages, ['webman/console']));

// laravel 应用：无 webman 依赖，验证无 webman 环境下的行为
$laravel = AppConfig::configure()
    ->skeleton('laravel/laravel', '^12.0') // 与根仓库 illuminate 12 同代
    ->targetDir('laravel')
    ->srcDir('laravel-src')
    ->package('webman-tech/common-utils', '../packages/*')
    ->package('webman-tech/dto', '../packages/*')
    ->package('webman-tech/logger', '../packages/*')
    ->require([
        // 无任何 webman 依赖，彻底验证无 webman 环境下的行为
        'webman-tech/common-utils' => 'dev-main',
        'webman-tech/dto' => 'dev-main',
        'webman-tech/logger' => 'dev-main',
    ])
    ->requireDev([
        'pestphp/pest' => '^3.8',
        // laravel 骨架测试为 PHPUnit 风格，补 pest 的 laravel 插件后可用 Pest 风格（与 webman e2e 统一）
        'pestphp/pest-plugin-laravel' => '^3.0',
    ])
    ->reinstallPackages([]);

return SetupConfig::configure()
    ->app('webman', $webman)
    ->app('laravel', $laravel);
