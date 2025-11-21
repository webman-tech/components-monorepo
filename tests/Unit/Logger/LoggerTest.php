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
