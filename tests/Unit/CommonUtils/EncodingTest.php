<?php

use WebmanTech\CommonUtils\Encoding;

test('encode utf8', function () {
    $txtISO88591 = fixture_get_content('CommonUtils/encodingTxt/iso_8859_1_aou.txt');
    $txtISO88592 = fixture_get_content('CommonUtils/encodingTxt/iso_8859_1_has_aou.txt');
    $txtGB2312 = fixture_get_content('CommonUtils/encodingTxt/gb2312_chinese.txt');
    $txtGBK = fixture_get_content('CommonUtils/encodingTxt/gbk_chinese.txt');

    expect(Encoding::toUTF8('UTF8字符串'))->toBe('UTF8字符串')
        ->and(Encoding::toUTF8($txtISO88591))->toBe('äöü')
        ->and(Encoding::toUTF8($txtISO88592))->toBeString() // 界定的不准，会导致解析不对，但是不至于解析失败
        ->and(Encoding::toUTF8($txtGB2312))->toBeString() // 界定的不准，会导致解析不对，但是不至于解析失败
        ->and(Encoding::toUTF8($txtGBK))->toBeString() // 界定的不准，会导致解析不对，但是不至于解析失败
    ;
});

test('encode utf8 with different types', function () {
    $txt = fixture_get_content('CommonUtils/encodingTxt/iso_8859_1_aou.txt');

    $obj = new stdClass();
    expect(Encoding::toUTF8($txt))->toBe('äöü') // string 非 utf8
    ->and(Encoding::toUTF8(['a' => $txt]))->toBe(['a' => 'äöü']) // array value 非 utf8
    ->and(Encoding::toUTF8([$txt => 'x']))->toBe(['äöü' => 'x']) // array key 非 utf8
    ->and(Encoding::toUTF8(null))->toBeNull() // null
    ->and(Encoding::toUTF8(false))->toBeFalse() // false
    ->and(Encoding::toUTF8($obj))->toBe($obj) // object
    ;
});
