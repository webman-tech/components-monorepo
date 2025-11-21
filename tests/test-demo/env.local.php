<?php

use function WebmanTech\CommonUtils\put_env;

require __DIR__ . '/env.php';

put_env('TEST_OVERWRITE_ABC', 'overwrite_abc');


// 放在最后，用于加载 test 环境自定义配置

if (file_exists(__DIR__ . '/env.local.test.php')) {
    require __DIR__ . '/env.local.test.php';
}
