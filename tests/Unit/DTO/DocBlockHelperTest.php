<?php

use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem;
use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem2;
use Tests\Fixtures\DTO\DocBlockArrayItemExtractor;
use Tests\Fixtures\DTO\DocBlockArrayItemExtractorItem;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\Helper\DocBlockHelper;

test('extractVarTypes', function () {
    $reflectionClass = new ReflectionClass(DocBlockArrayItemExtractor::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_string'));
    expect($result->string)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_string2'));
    expect($result->string)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_int'));
    expect($result->integer)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_bool'));
    expect($result->boolean)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_float'));
    expect($result->numeric)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_object_same_namespace'));
    expect($result)->toBe(DocBlockArrayItemExtractorItem::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_object_full_class'));
    expect($result)->toBe(DocBlockArrayItemExtractorDirItem::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_object_use_class'));
    expect($result)->toBe(DocBlockArrayItemExtractorDirItem2::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_object_half_use_class'));
    expect($result)->toBe(DocBlockArrayItemExtractorDirItem2::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_object_use_as_class'));
    expect($result)->toBe(DocBlockArrayItemExtractorDirItem2::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_string'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->string)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_int'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->integer)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_float'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->numeric)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_bool'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->boolean)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object_nullable'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class)
        ->and($result->nullable)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object_nullable_reverse'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class)
        ->and($result->nullable)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object_array'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBeInstanceOf(ValidationRules::class)
        ->and($result->arrayItem->object)->toBeNull()
        ->and($result->arrayItem->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class);

    // 紧凑写法（逗号后无空格）：此前 explode(', ', ...) 会越界触发 Undefined array key 1 warning
    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_int_no_space'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->integer)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_string_no_space'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem->string)->toBeTrue();

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object_no_space'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class);

    // 无空格 + 嵌套（value 为数组）：验证 explode(',', ..., 2) 在嵌套下也正确切出 value
    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_object_array_no_space'));
    expect($result->object)->toBeTrue()
        ->and($result->arrayItem)->toBeInstanceOf(ValidationRules::class)
        ->and($result->arrayItem->object)->toBeNull()
        ->and($result->arrayItem->arrayItem)->toBe(DocBlockArrayItemExtractorItem::class);

    // 无空格 + value 含内层逗号（array<k, array<...>>）：
    // 验证 limit=2 不会被内层逗号切错、该分支不触发 warning/异常。
    // 注：嵌套 map value 因 parseSingleType 不解析 array<...> 语法，返回 null 是既有行为（带空格同款亦如此）。
    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('object_nested_map_no_space'));
    expect($result)->toBeNull();
});
