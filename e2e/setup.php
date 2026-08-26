<?php
/**
 * E2E 测试应用安装命令
 *
 * 生成物（e2e/webman、e2e/laravel）是被 git 忽略的可抛弃目录：
 * 官方骨架升级时删除对应目录后重新执行本命令即可。
 *
 * 用法：
 *   php e2e/setup.php webman          # 完整安装 webman e2e 应用（删除重建）
 *   php e2e/setup.php laravel         # 完整安装 laravel e2e 应用（删除重建）
 *   php e2e/setup.php all             # 全部安装
 *   php e2e/setup.php webman --sync   # 仅同步自有代码（dev 快速迭代）
 *
 * 完整安装流程（顺序关键）：
 *   1. composer create-project 官方骨架
 *   2. patch composer.json（path repository + 组件依赖）
 *   3. composer update（触发包内 Install.php 落地 webman 插件配置）
 *   4. copy *-src 自有代码到应用目录（覆盖式，保证自有 config 覆盖在插件默认配置之后生效）
 */

declare(strict_types=1);

const ROOT_DIR = __DIR__ . '/..';

// path repository 中钉死的本地包版本
const LOCAL_PACKAGES = [
    'webman-tech/amis-admin' => 'dev-main',
    'webman-tech/auth' => 'dev-main',
    'webman-tech/common-utils' => 'dev-main',
    'webman-tech/crontab-task' => 'dev-main',
    'webman-tech/debugbar' => 'dev-main',
    'webman-tech/dto' => 'dev-main',
    'webman-tech/log-reader' => 'dev-main',
    'webman-tech/logger' => 'dev-main',
    'webman-tech/swagger' => 'dev-main',
];

/**
 * 官方骨架与自有代码的安装定义
 */
function app_definitions(): array
{
    return [
        'webman' => [
            'skeleton' => 'workerman/webman',
            'target_dir' => ROOT_DIR . '/e2e/webman',
            'src_dir' => ROOT_DIR . '/e2e/webman-src',
            'require' => array_merge(LOCAL_PACKAGES, [
                // 仅安装被测包声明的依赖 + 单一框架，验证各包依赖声明完整性
                'webman-tech/laravel-monorepo' => '12.x-dev', // webman 环境下提供 validator() helper
                'tinywan/jwt' => '^1.11', // auth 包的可选依赖，验证可选依赖场景
            ]),
            'require_dev' => [
                'pestphp/pest' => '^3.8',
                'symfony/http-client' => '^7.3',
                'symfony/process' => '^7.3',
                // 加载 plugin 的 command.php，验证 crontab-task 等包的 CLI 命令集成链路
                'webman/console' => '^2.0',
            ],
            // 骨架自带 monolog ^2，与 laravel-monorepo 拉入的 illuminate 12（monolog ^3）冲突，升级为 ^3
            'require_override' => [
                'monolog/monolog' => '^3.0',
            ],
            // 批量 composer update 时 composer 进程内 autoloader 未就绪，包内 Install.php 不会触发；
            // 需 reinstall（单包流程会刷新 autoloader，走 post-package-install -> support\Plugin::install 真实安装链）。
            // webman/console 也是 webman 插件（Install 落地 `webman` CLI 入口 + config/plugin），需一并 reinstall
            'reinstall_packages' => array_merge(array_keys(LOCAL_PACKAGES), [
                'webman/console',
            ]),
        ],
        'laravel' => [
            'skeleton' => 'laravel/laravel',
            'skeleton_version' => '^12.0', // 与根仓库 illuminate 12 同代
            'target_dir' => ROOT_DIR . '/e2e/laravel',
            'src_dir' => ROOT_DIR . '/e2e/laravel-src',
            'require' => [
                // 无任何 webman 依赖，彻底验证无 webman 环境下的行为
                'webman-tech/common-utils' => LOCAL_PACKAGES['webman-tech/common-utils'],
                'webman-tech/dto' => LOCAL_PACKAGES['webman-tech/dto'],
                'webman-tech/logger' => LOCAL_PACKAGES['webman-tech/logger'],
            ],
            'require_dev' => [
                'pestphp/pest' => '^3.8',
                // laravel 骨架测试为 PHPUnit 风格，补 pest 的 laravel 插件后可用 Pest 风格（与 webman e2e 统一）
                'pestphp/pest-plugin-laravel' => '^3.0',
            ],
            'require_override' => [],
            'reinstall_packages' => [],
        ],
    ];
}

function main(array $argv): int
{
    $apps = app_definitions();
    $targets = array_keys($apps);
    $syncOnly = in_array('--sync', $argv, true);
    $names = array_values(array_filter(array_slice($argv, 1), fn($arg) => !str_starts_with($arg, '--')));

    if (count($names) === 0) {
        fwrite(STDERR, "用法: php e2e/setup.php " . implode('|', $targets) . "|all [--sync]\n");
        return 1;
    }
    if (count($names) > 1) {
        fwrite(STDERR, "仅支持一次安装一个应用\n");
        return 1;
    }
    $name = $names[0];
    if ($name === 'all') {
        $names = $targets;
    } elseif (!isset($apps[$name])) {
        fwrite(STDERR, "未知应用: {$name}（可选: " . implode('|', $targets) . "|all）\n");
        return 1;
    } else {
        $names = [$name];
    }

    foreach ($names as $app) {
        if ($syncOnly) {
            sync_src($apps[$app]);
        } else {
            install_app($apps[$app]);
        }
    }

    return 0;
}

function install_app(array $def): void
{
    $target = $def['target_dir'];
    $src = $def['src_dir'];

    echo "==> [1/5] 生成官方骨架 {$def['skeleton']} -> {$target}\n";
    if (is_dir($target)) {
        run_cmd(['rm', '-rf', $target]);
    }
    // create-project 参数顺序：package <directory> <version>（version 必须在 directory 之后）
    $create = ['composer', 'create-project', $def['skeleton'], $target];
    if (!empty($def['skeleton_version'])) {
        $create[] = $def['skeleton_version'];
    }
    $create[] = '--no-interaction';
    $create[] = '--no-progress';
    run_cmd($create);

    echo "==> [2/5] patch composer.json\n";
    patch_composer_json($def);

    echo "==> [3/5] composer update（安装依赖）\n";
    run_cmd(['composer', 'update', '--no-interaction', '--no-progress'], $target, [
        // 同一 git 仓库时 path repository 自动 carry-over 该版本，CI detached HEAD 兜底
        'COMPOSER_ROOT_VERSION' => 'dev-main',
    ]);

    if (!empty($def['reinstall_packages'])) {
        echo "==> [4/5] composer reinstall（触发包内 Install.php 真实安装链）\n";
        run_cmd(
            array_merge(['composer', 'reinstall', '--no-interaction', '--no-progress'], $def['reinstall_packages']),
            $target,
            ['COMPOSER_ROOT_VERSION' => 'dev-main'],
        );
    } else {
        echo "==> [4/5] 跳过 reinstall（无 webman 插件包）\n";
    }

    echo "==> [5/5] 同步自有代码 {$src} -> {$target}\n";
    sync_src($def);

    echo "==> 完成。运行测试: cd {$target} && vendor/bin/pest\n";
}

/**
 * 仅同步自有代码（dev 快速迭代）
 * 注意：必须在 composer update 之后执行，否则自有 config 覆盖会缺前置的插件默认配置
 */
function sync_src(array $def): void
{
    $src = rtrim($def['src_dir'], '/');
    $target = $def['target_dir'];
    if (!is_dir($src)) {
        fwrite(STDERR, "源目录不存在: {$src}\n");
        exit(1);
    }
    if (!is_dir($target)) {
        fwrite(STDERR, "目标目录不存在: {$target}（先完整安装: php e2e/setup.php <app>）\n");
        exit(1);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $targetPath = $target . str_replace($src, '', (string)$item);
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            if (!is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0755, true);
            }
            copy((string)$item, $targetPath);
            echo "  sync {$targetPath}\n";
        }
    }
}

/**
 * patch 应用的 composer.json：
 * - 追加 path repository（symlink + versions 钉 dev-main）
 * - 追加组件依赖（保留官方骨架全部 scripts 等原有内容）
 */
function patch_composer_json(array $def): void
{
    $file = $def['target_dir'] . '/composer.json';
    $content = file_get_contents($file);
    if ($content === false) {
        throw new RuntimeException("无法读取 {$file}");
    }
    $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    $json['repositories'][] = [
        'type' => 'path',
        'url' => '../../packages/*',
        'options' => [
            'symlink' => true,
            'versions' => LOCAL_PACKAGES,
        ],
    ];
    foreach ($def['require'] as $package => $version) {
        $json['require'][$package] = $version;
    }
    foreach ($def['require_override'] as $package => $version) {
        $json['require'][$package] = $version;
    }
    foreach ($def['require_dev'] as $package => $version) {
        $json['require-dev'][$package] = $version;
    }
    $json['config'] = array_merge($json['config'] ?? [], [
        'allow-plugins' => array_merge($json['config']['allow-plugins'] ?? [], [
            'pestphp/pest-plugin' => true,
        ]),
    ]);
    // 骨架自带 minimum-stability: dev + prefer-stable: true（webman），laravel 骨架无则补齐，
    // 保证 dev-main / 12.x-dev 可解析
    $json['minimum-stability'] = 'dev';
    $json['prefer-stable'] = true;

    // laravel 骨架的 post-autoload-dump 会执行 @php artisan package:discover，
    // laravel-src 同步的 phpunit.xml/tests 之前该步骤正常；无需改动 scripts
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
}

/**
 * 执行命令并透传输出，失败时中止
 */
function run_cmd(array $command, ?string $cwd = null, array $env = []): void
{
    $commandLine = implode(' ', array_map('escapeshellarg', $command));
    $prefix = '';
    if ($cwd !== null) {
        // cd 必须由 shell 内建执行（外部 cd 无法改变父 shell 的 cwd）
        $prefix = 'cd ' . escapeshellarg($cwd) . ' && ';
    }
    if ($env !== []) {
        // 环境变量通过 env 命令传递（key=value 整体转义后 shell 会将其当作命令）
        $prefix .= 'env ';
        foreach ($env as $key => $value) {
            $prefix .= escapeshellarg("{$key}={$value}") . ' ';
        }
    }

    passthru($prefix . $commandLine, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "\n命令执行失败(exit {$exitCode}): {$commandLine}\n");
        exit($exitCode);
    }
}

exit(main($argv));
