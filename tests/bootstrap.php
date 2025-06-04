<?php

function get_env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

copy_dir(__DIR__ . '/webman', base_path());

require_once __DIR__ . '/../vendor/workerman/webman-framework/src/support/bootstrap.php';
