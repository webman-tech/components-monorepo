<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Support\Facades\App;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 语言相关
 */
final class Lang
{
    /**
     * 获取语言
     * @return string
     */
    public static function getLocale(): string
    {
        return match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_LANG_GET_LOCALE) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_LANG_GET_LOCALE),
            Runtime::isWebman() => \locale(),
            Runtime::isLaravel() => App::currentLocale(),
            default => throw new UnsupportedRuntime(),
        };
    }

    /**
     * 设置语言
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_LANG_SET_LOCALE) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_LANG_SET_LOCALE, $locale),
            Runtime::isWebman() => \locale($locale),
            Runtime::isLaravel() => App::setLocale($locale),
            default => throw new UnsupportedRuntime(),
        };
    }
}
