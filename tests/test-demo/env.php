<?php

use function WebmanTech\CommonUtils\get_env;
use function WebmanTech\CommonUtils\put_env;

put_env('TEST_ABC', 'abc');
put_env('TEST_FROM_ABC', get_env('TEST_ABC'));
put_env('TEST_OVERWRITE_ABC', 'abc');

!defined('DEFINE_ABC') && define('DEFINE_ABC', 'define_abc');
