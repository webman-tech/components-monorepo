<?php

use WebmanTech\CommonUtils\Constants;
use WebmanTech\CommonUtils\Runtime;

test('runtime switching toggles environment flags', function () {
    $original = Runtime::getCurrent();

    Runtime::changeRuntime(Constants::RUNTIME_WEBMAN);
    expect(Runtime::isWebman())->toBeTrue()
        ->and(Runtime::isWorkerman())->toBeTrue()
        ->and(Runtime::isLaravel())->toBeFalse()
        ->and(Runtime::isCustom())->toBeFalse();

    Runtime::changeRuntime(Constants::RUNTIME_LARAVEL);
    expect(Runtime::isLaravel())->toBeTrue()
        ->and(Runtime::isWebman())->toBeFalse()
        ->and(Runtime::isWorkerman())->toBeFalse();

    Runtime::changeRuntime(Constants::RUNTIME_CUSTOM);
    expect(Runtime::isCustom())->toBeTrue()
        ->and(Runtime::getCurrent())->toBe(Constants::RUNTIME_CUSTOM)
        ->and(Runtime::isCli())->toBeTrue();

    Runtime::changeRuntime($original);
});
