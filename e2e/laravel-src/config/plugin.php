<?php

use Monolog\Processor\PsrLogMessageProcessor;
use WebmanTech\Logger\Formatter\ChannelFormatter;
use WebmanTech\Logger\Mode;
use WebmanTech\Logger\Processors;

/**
 * e2e：logger 包的 log-channel 配置
 *
 * 包内 ConfigHelper 读取 config('plugin.webman-tech.logger.log-channel')，
 * laravel 的 dot 语法即 plugin['webman-tech']['logger']['log-channel']，
 * 故以本文件承载（对应 webman 下的 config/plugin/webman-tech/logger/log-channel.php）。
 */
return [
    'webman-tech' => [
        'logger' => [
            'log-channel' => [
                'channels' => [
                    'httpRequest',
                ],
                'levels' => [
                    'default' => 'debug',
                ],
                'processors' => function () {
                    return [
                        new PsrLogMessageProcessor('Y-m-d H:i:s', true),
                        new Processors\RequestRouteProcessor(),
                        new Processors\RequestIpProcessor(),
                        new Processors\RequestTraceProcessor(),
                    ];
                },
                'modes' => [
                    'split' => [
                        'class' => Mode\SplitMode::class,
                        'enable' => true,
                        'except_channels' => [],
                        'only_channels' => [],
                        'formatter' => [
                            'class' => ChannelFormatter::class,
                        ],
                        'max_files' => 30,
                    ],
                ],
            ],
        ],
    ],
];
