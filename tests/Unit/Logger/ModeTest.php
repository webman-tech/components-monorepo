<?php

use Monolog\Handler\RedisHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use WebmanTech\Logger\Mode\MixMode;
use WebmanTech\Logger\Mode\RedisMode;
use WebmanTech\Logger\Mode\SplitMode;
use WebmanTech\Logger\Mode\StdoutMode;

test('通用配置', function () {
    // 不启用
    $mode = new SplitMode([
        'enable' => false,
    ]);
    expect($mode->getHandler('test', 'info'))->toBe([]);

    // 启用，但排除部分 channel
    $mode = new SplitMode([
        'enable' => true,
        'except_channels' => ['except'],
    ]);
    expect($mode->getHandler('test', 'info'))->not->toBeEmpty()
        ->and($mode->getHandler('except', 'info'))->toBe([]);

    // 启用，仅针对部分 channel
    $mode = new SplitMode([
        'enable' => true,
        'only_channels' => ['only'],
    ]);
    expect($mode->getHandler('only', 'info'))->not->toBeEmpty()
        ->and($mode->getHandler('test', 'info'))->toBe([]);
});

test('SplitMode', function () {
    $mode = new SplitMode([
        'enable' => true,
        'max_files' => 60,
    ]);
    $handler = $mode->getHandler('test', 'info');
    expect($handler['class'])->toBe(RotatingFileHandler::class)
        ->and($handler['constructor']['filename'])->toContain('test')
        ->and($handler['constructor']['maxFiles'])->toBe(60)
        ->and($handler['constructor']['level'])->toBe('info');
});

test('StdoutMode', function () {
    $mode = new StdoutMode([
        'enable' => true,
    ]);
    $handler = $mode->getHandler('test', 'info');
    expect($handler['class'])->toBe(StreamHandler::class)
        ->and($handler['constructor']['stream'])->toBe('php://stdout')
        ->and($handler['constructor']['level'])->toBe('info');
});

test('RedisMode', function () {
    $mode = new RedisMode([
        'enable' => true,
        'redis' => 'default',
    ]);
    $handler = $mode->getHandler('test', 'info');
    expect($handler['class'])->toBe(RedisHandler::class)
        ->and($handler['constructor']['redis'])->toBe('default')
        ->and($handler['constructor']['key'])->toBe('webmanLog:test')
        ->and($handler['constructor']['capSize'])->toBe(0)
        ->and($handler['constructor']['level'])->toBe('info');
});

test('MixMode', function () {
    $mode = new MixMode([
        'enable' => true,
    ]);
    $handler = $mode->getHandler('test', 'info');
    expect($handler['class'])->toBe(RotatingFileHandler::class)
        ->and($handler['constructor']['filename'])->toContain('channelMixed')
        ->and($handler['constructor']['maxFiles'])->toBe(30)
        ->and($handler['constructor']['level'])->toBe('info');
});
