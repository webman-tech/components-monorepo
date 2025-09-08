<?php

use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem;
use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem2;
use Tests\Fixtures\DTO\DocBlockArrayItemExtractor;
use Tests\Fixtures\DTO\DocBlockArrayItemExtractorItem;
use WebmanTech\DTO\Helper\DocBlockHelper;

test('extractVarTypes', function () {
    $reflectionClass = new ReflectionClass(DocBlockArrayItemExtractor::class);

    $result = DocBlockHelper::extractClassPropertyArrayItemType($reflectionClass->getProperty('array_string'));
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
});
