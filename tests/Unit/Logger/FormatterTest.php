<?php

use Monolog\Level;
use Monolog\LogRecord;
use WebmanTech\Logger\Formatter\ChannelFormatter;
use WebmanTech\Logger\Formatter\ChannelMixedFormatter;

function create_log_record(array $extra = [], array $context = []): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable('2024-01-01 00:00:00'),
        channel: 'app',
        level: Level::Info,
        message: 'hello world',
        context: $context,
        extra: $extra,
    );
}

test('ChannelFormatter renders structured message', function () {
    $formatter = new ChannelFormatter('[app]');
    $record = create_log_record([
        'traceId' => 'trace-1',
        'ip' => '127.0.0.1',
        'userId' => '42',
        'route' => 'GET:/health',
    ], [
        'foo' => 'bar',
    ]);

    $formatted = $formatter->format($record);
    expect($formatted)->toContain('[app][INFO][127.0.0.1][42][GET:/health]')
        ->and($formatted)->toContain('hello world')
        ->and($formatted)->toContain('"foo":"bar"');
});

test('ChannelMixedFormatter injects channel name placeholder', function () {
    $formatter = new ChannelMixedFormatter();
    $record = create_log_record([
        'traceId' => 'trace-2',
        'ip' => '10.0.0.1',
        'userId' => '7',
        'route' => 'POST:/items',
    ]);

    $formatted = $formatter->format($record);
    expect($formatted)->toContain('[app][INFO]')
        ->and($formatted)->toContain('[POST:/items]')
        ->and($formatted)->toContain('trace-2');
});
