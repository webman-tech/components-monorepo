<?php

// common-utils 的 Runtime 自动检测（不设置 Runtime::changeRuntime，走 null 自动检测分支）

use WebmanTech\CommonUtils\Runtime;

test('laravel 环境下 Runtime 自动检测正确', function () {
    expect(Runtime::isLaravel())->toBeTrue()
        ->and(Runtime::isWebman())->toBeFalse()
        ->and(Runtime::isCustom())->toBeFalse()
        ->and(Runtime::getCurrent())->toBeNull()
        ->and(Runtime::isCli())->toBeTrue();
});
