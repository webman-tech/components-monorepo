<?php

namespace Tests\Fixtures\Logger;

use WebmanTech\Logger\Logger;

/**
 * @method static void logger_test($msg, string $type = 'info', array $context = [])
 */
class MyLogger extends Logger
{
    public static function getChannels()
    {
        return [
            'logger_test',
        ];
    }

    public static function getLogFile(string $channel, bool $clean = false, bool $delete = false): string
    {
        $file = runtime_path('logs/' . $channel . '/' . $channel . '-' . date('Y-m-d') . '.log');
        if (file_exists($file)) {
            if ($clean) {
                file_put_contents($file, $clean);
            }
            if ($delete) {
                unlink($file);
            }
        }
        return $file;
    }
}
