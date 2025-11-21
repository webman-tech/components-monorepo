<?php

namespace WebmanTech\CommonUtils\Testing;

final class TestLang
{
    public static string $locale = 'zh_CN';

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }
}
