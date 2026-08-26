<?php

return array_merge(
    // 官方骨架的默认 channel
    [
        'default' => [
            'handlers' => [
                [
                    'class' => Monolog\Handler\RotatingFileHandler::class,
                    'constructor' => [
                        runtime_path() . '/logs/webman.log',
                        7,
                        Monolog\Logger::DEBUG,
                    ],
                    'formatter' => [
                        'class' => Monolog\Formatter\LineFormatter::class,
                        'constructor' => [null, 'Y-m-d H:i:s', true],
                    ],
                ],
            ],
        ],
    ],
    // webman-tech/logger 的 channels（含 httpRequest 等，按 SplitMode 分目录记录）
    \WebmanTech\Logger\Logger::getLogChannelConfigs(),
);
