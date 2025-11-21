<?php

use WebmanTech\CommonUtils\Json;
use WebmanTech\CommonUtils\Json\Expression;

test('encode simple', function () {
    expect(Json::encode(['a' => 'x']))->toBe('{"a":"x"}')
        ->and(Json::encode(['a' => '你好']))->toBe('{"a":"你好"}') // 中文保留
        ->and(Json::encode(['a' => 'abc/xyz']))->toBe('{"a":"abc/xyz"}') // 不要转移斜杠
        ->and(Json::encode(new \stdClass()))->toBe('{}') // 转为对象
        ->and(Json::encode(['a' => INF]))->toBe('{"a":0}') // INF 不报错
        ->and(Json::encode(['a' => NAN]))->toBe('{"a":0}') // NAN 不报错
    ;
});

test('decode simple', function () {
    expect(Json::decode('{"a":"x"}'))->toBe(['a' => 'x']) // 自动转为数组
    ;
});

test('encode depth', function () {
    $xdebugMaxDepth = ini_get('xdebug.max_nesting_level');
    $xdebugMaxDepthChanged = false;
    if ($xdebugMaxDepth < 1000) {
        ini_set('xdebug.max_nesting_level', 1000);
        $xdebugMaxDepthChanged = true;
    }

    $deepArray = [];
    for ($i = 0; $i < 511; $i++) {
        $deepArray = [$deepArray];
    }
    $deepArrayOver = [$deepArray];
    expect(Json::encode($deepArray))->toBeString() // 512 层嵌套正常
    ->and(fn() => Json::encode($deepArrayOver))->toThrow(JsonException::class) // 513 层嵌套报错
    ->and(Json::encode($deepArrayOver, throw: false))->toBe('') // 抑制错误
    ;

    if ($xdebugMaxDepthChanged) {
        ini_set('xdebug.max_nesting_level', $xdebugMaxDepth);
    }
});

test('encode with Expression', function () {
    $cases = [
        [
            'input' => ['foo' => new Expression('function() { console.log("this js script"); }')],
            'output' => '{"foo":function() { console.log("this js script"); }}',
            'output_php' => '{"foo":{"expression":"function() { console.log(\"this js script\"); }"}}'
        ],
    ];

    foreach ($cases as $case) {
        expect(Json::encode($case['input']))->toBe($case['output'])
            ->and(json_encode($case['input']))->toBe($case['output_php']);
    }
});

test('encode/decode not utf8', function () {
    $txt = fixture_get_content('CommonUtils/encodingTxt/iso_8859_1_aou.txt');

    expect(Json::encode($txt))->toBe('"äöü"')
        ->and(Json::encode(['a' => $txt]))->toBe('{"a":"äöü"}')
        ->and(Json::decode('"' . $txt . '"'))->toBe('äöü')
        ->and(Json::decode('{"a":"' . $txt . '"}'))->toBe(['a' => 'äöü']);
});

test('encode resource', function () {
    $resource = fopen(__FILE__, 'r');
    expect(Json::encode(['body' => $resource]))->toBe('{"body":"__RESOURCE__"}');
});
