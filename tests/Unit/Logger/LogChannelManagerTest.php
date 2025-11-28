<?php

use Tests\Fixtures\Logger\FakeMode;
use WebmanTech\Logger\LogChannelManager;
use WebmanTech\Logger\Processors\RequestIpProcessor;
use WebmanTech\Logger\Processors\RequestRouteProcessor;

test('build log channel configs with processors and per-channel levels', function () {
    $manager = new LogChannelManager([
        'channels' => ['app', 'sql', 'skip'],
        'modes' => [
            [
                'class' => FakeMode::class,
                'enable' => true,
                'except_channels' => ['skip'],
            ],
        ],
        'levels' => [
            'default' => 'info',
            'special' => [
                'sql' => 'debug',
            ],
        ],
        'processors' => function () {
            return [
                new RequestIpProcessor(),
                new RequestRouteProcessor(),
            ];
        },
    ]);

    $configs = $manager->buildLogChannelConfigs();

    expect($configs)->toHaveKey('app')
        ->and($configs)->toHaveKey('sql')
        ->and($configs)->not->toHaveKey('skip');

    $appHandler = $configs['app']['handlers'][0];
    expect($appHandler['constructor']['channel'])->toBe('app')
        ->and($appHandler['constructor']['level'])->toBe('info');

    $sqlHandler = $configs['sql']['handlers'][0];
    expect($sqlHandler['constructor']['channel'])->toBe('sql')
        ->and($sqlHandler['constructor']['level'])->toBe('debug');

    expect($configs['app']['processors'])->toHaveCount(2)
        ->and($configs['app']['processors'][0])->toBeInstanceOf(RequestIpProcessor::class)
        ->and($configs['app']['processors'][1])->toBeInstanceOf(RequestRouteProcessor::class);
});

test('processors definition must return processor instances', function () {
    $manager = new LogChannelManager([
        'channels' => ['app'],
        'modes' => [
            [
                'class' => FakeMode::class,
                'enable' => true,
            ],
        ],
        'processors' => fn() => 'invalid',
    ]);

    expect(fn() => $manager->buildLogChannelConfigs())
        ->toThrow(\InvalidArgumentException::class, 'processors 必须是数组或者 callable 返回数组');
});
