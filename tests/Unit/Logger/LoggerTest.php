<?php

use Tests\Fixtures\Logger\MyLogger;
use WebmanTech\CommonUtils\Log;
use WebmanTech\CommonUtils\Testing\TestLogger;

test('日志写入', function () {
    $logger = TestLogger::channel('logger_test');
    $logger->flush();

    MyLogger::logger_test('abc');
    MyLogger::logger_test('abc', 'warning');
    MyLogger::logger_test('abc', 'warning', ['a' => 1]);
    Log::channel('logger_test')->info('xyz');
    Log::channel('logger_test')->warning('xyz');
    Log::channel('logger_test')->warning('xyz', ['a' => 1]);

    $all = $logger->getAll();
    expect($all[0]['level'])->toBe('INFO')
        ->and($all[0]['message'])->toBe('abc')
        ->and($all[0]['context'])->toBe([])
        ->and($all[1]['level'])->toBe('WARNING')
        ->and($all[1]['message'])->toBe('abc')
        ->and($all[1]['context'])->toBe([])
        ->and($all[2]['level'])->toBe('WARNING')
        ->and($all[2]['message'])->toBe('abc')
        ->and($all[2]['context'])->toBe(['a' => 1])
        ->and($all[3]['level'])->toBe('INFO')
        ->and($all[3]['message'])->toBe('xyz')
        ->and($all[3]['context'])->toBe([])
        ->and($all[4]['level'])->toBe('WARNING')
        ->and($all[4]['message'])->toBe('xyz')
        ->and($all[4]['context'])->toBe([])
        ->and($all[5]['level'])->toBe('WARNING')
        ->and($all[5]['message'])->toBe('xyz')
        ->and($all[5]['context'])->toBe(['a' => 1]);
});

test('命名参数支持', function () {
    $logger = TestLogger::channel('logger_test');
    $logger->flush();

    MyLogger::logger_test('abc', level: 'warning');
    MyLogger::logger_test('def', context: ['b' => 2]);
    MyLogger::logger_test('ghi', level: 'error', context: ['c' => 3]);
    MyLogger::logger_test('jkl', context: ['d' => 4], level: 'debug');

    $all = $logger->getAll();
    expect($all[0]['level'])->toBe('WARNING')
        ->and($all[0]['message'])->toBe('abc')
        ->and($all[0]['context'])->toBe([])
        ->and($all[1]['level'])->toBe('INFO')
        ->and($all[1]['message'])->toBe('def')
        ->and($all[1]['context'])->toBe(['b' => 2])
        ->and($all[2]['level'])->toBe('ERROR')
        ->and($all[2]['message'])->toBe('ghi')
        ->and($all[2]['context'])->toBe(['c' => 3])
        ->and($all[3]['level'])->toBe('DEBUG')
        ->and($all[3]['message'])->toBe('jkl')
        ->and($all[3]['context'])->toBe(['d' => 4]);
});

test('type 作为 level 别名的命名参数支持', function () {
    $logger = TestLogger::channel('logger_test2');
    $logger->flush();

    MyLogger::logger_test2('abc', type: 'warning');
    MyLogger::logger_test2('def', context: ['b' => 2]);
    MyLogger::logger_test2('ghi', type: 'error', context: ['c' => 3]);
    MyLogger::logger_test2('jkl', context: ['d' => 4], type: 'debug');

    $all = $logger->getAll();
    expect($all[0]['level'])->toBe('WARNING')
        ->and($all[0]['message'])->toBe('abc')
        ->and($all[0]['context'])->toBe([])
        ->and($all[1]['level'])->toBe('INFO')
        ->and($all[1]['message'])->toBe('def')
        ->and($all[1]['context'])->toBe(['b' => 2])
        ->and($all[2]['level'])->toBe('ERROR')
        ->and($all[2]['message'])->toBe('ghi')
        ->and($all[2]['context'])->toBe(['c' => 3])
        ->and($all[3]['level'])->toBe('DEBUG')
        ->and($all[3]['message'])->toBe('jkl')
        ->and($all[3]['context'])->toBe(['d' => 4]);
});
