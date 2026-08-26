<?php

use Monolog\Processor\PsrLogMessageProcessor;
use WebmanTech\Logger\Formatter\ChannelFormatter;
use WebmanTech\Logger\Mode;
use WebmanTech\Logger\Processors;

// 在插件默认配置基础上启用 httpRequest channel（HttpRequestLogMiddleware 写入）
return [
    'channels' => [
        'httpRequest',
    ],
    'levels' => [
        'default' => config('app.debug') ? 'debug' : 'info',
        'special' => [],
    ],
    'processors' => fn() => [
        new PsrLogMessageProcessor('Y-m-d H:i:s', true),
        new Processors\RequestRouteProcessor(),
        new Processors\RequestIpProcessor(),
        new Processors\AuthUserIdProcessor(),
        new Processors\RequestTraceProcessor(),
    ],
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
];
