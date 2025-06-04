<?php
/**
 * 清理本地无用的文件目录（比如一些测试目录）
 */

use Illuminate\Filesystem\Filesystem;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';

$paths = [
    'app',
    'config',
    'plugin',
    'resource',
    'runtime',
    'storage',
    'artisan',
];

$filesystem = new Filesystem();

foreach ($paths as $path) {
    $path = root_path($path);
    if ($filesystem->isDirectory($path)) {
        $filesystem->deleteDirectory($path);
        write_log("delete path: $path");
    } elseif ($filesystem->isFile($path)) {
        $filesystem->delete($path);
        write_log("delete file: $path");
    }
}
