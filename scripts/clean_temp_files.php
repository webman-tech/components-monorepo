<?php
/**
 * 清理本地无用的文件目录（比如一些测试目录）
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/_common.php';

$paths = [
    'app',
    'config',
    'plugin',
    'resource',
    'runtime',
];

foreach ($paths as $path) {
    $path = get_path($path);
    if (is_dir($path)) {
        remove_dir($path);
        log_line("delete path: $path");
    } elseif (is_file($path)) {
        unlink($path);
        log_line("delete file: $path");
    }
}
