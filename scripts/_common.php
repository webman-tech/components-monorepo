<?php

function log_line(string $msg): void
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . "\n";
}

function run_shell(string $shell, ?string $cwd = null): void
{
    $cwd ??= get_path();
    $descriptorspec = [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
    ];

    log_line("执行命令: $shell, cwd: $cwd");

    // 执行命令，并且获取输出
    $process = proc_open($shell, $descriptorspec, $pipes, $cwd);
    if (is_resource($process)) {
        while (true) {
            $info = proc_get_status($process);
            if (!$info['running']) {
                break;
            }
            usleep(10000);
        }
        proc_close($process);
    }

    log_line("命令执行完毕");
}

function write_file(string $filename, string $content, bool $isAbsolutePath = false): void
{
    if (!$isAbsolutePath) {
        $filename = get_path($filename);
    }
    file_put_contents($filename, $content);

    log_line("File written: $filename");
}

function path_join(string $path, string $path2): string
{
    return rtrim(rtrim($path, '\\/') . DIRECTORY_SEPARATOR . ltrim($path2, '\\/'), '\\/');
}

function get_path(string $path = ''): string
{
    return path_join(realpath(__DIR__ . '/../'), $path);
}

function get_packages()
{
    static $packages;
    if ($packages) {
        return $packages;
    }

    $packagesDir = get_path('packages');
    $dirs = scandir($packagesDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') {
            // 跳过 . 和 .. 目录
            continue;
        }
        $packagePath = $packagesDir . '/' . $dir;
        if (!is_dir($packagePath) || str_starts_with($dir, '_')) {
            continue;
        }

        $dirName = str_replace($packagesDir . DIRECTORY_SEPARATOR, '', $dir);
        $composerName = 'webman-tech/' . $dirName;
        $gitName = 'packages/' . $dirName;

        $packages[] = [
            'dir_path' => $packagePath, // 包目录，全路径
            'dir_name' => $dirName, // 包目录名
            'composer_name' => $composerName, // composer包名
            'git_name' => $gitName, // git仓库名
        ];
    }

    return $packages;
}

// 自动安装依赖
//if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
//    run_shell("composer install", __DIR__ . '/../');
//}
//require __DIR__ . '/../vendor/autoload.php';
