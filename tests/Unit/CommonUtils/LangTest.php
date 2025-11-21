<?php

use WebmanTech\CommonUtils\Lang;
use WebmanTech\CommonUtils\Testing\TestLang;

test('getLocale', function () {
    expect(Lang::getLocale())->toBe(TestLang::$locale);
});

test('setLocale', function () {
    $default = Lang::getLocale();

    Lang::setLocale('zh-CN');
    expect(Lang::getLocale())->toBe('zh-CN');

    // 恢复
    Lang::setLocale($default);
});
