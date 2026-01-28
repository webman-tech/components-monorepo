<?php

namespace Tests\Fixtures\Logger;

use WebmanTech\Logger\Logger;

/**
 * @method static void logger_test($msg, string $level = 'info', array $context = [])
 * @method static void logger_test2($msg, string $type = 'info', array $context = [])
 */
class MyLogger extends Logger
{
    public static function getChannels()
    {
        return [
            'logger_test',
        ];
    }
}
