<?php

namespace Tests\Fixtures\DTO;

use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem2;
use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem2 as DocBlockArrayItemExtractorDirItem3;

final class DocBlockArrayItemExtractor
{
    /**
     * @var array|string[]
     */
    public array $array_string;
    /**
     * @var array<string>
     */
    public array $array_string2;
    /**
     * @var int[]
     */
    public array $array_int;
    /**
     * @var bool[]|array
     */
    public array $array_bool;
    /**
     * @var float[]
     */
    public array $array_float;
    /**
     * @var DocBlockArrayItemExtractorItem[]
     */
    public array $array_object_same_namespace;
    /**
     * @var \Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem[]
     */
    public array $array_object_full_class;
    /**
     * @var DocBlockArrayItemExtractorDirItem2[]
     */
    public array $array_object_use_class;
    /**
     * @var Dir\DocBlockArrayItemExtractorDirItem2[]
     */
    public array $array_object_half_use_class;
    /**
     * @var DocBlockArrayItemExtractorDirItem3[]
     */
    public array $array_object_use_as_class;

    /**
     * @var array<string, string>
     */
    public array $object_string;
    /**
     * @var array<string, int>
     */
    public array $object_int;
    /**
     * @var array<int, bool>
     */
    public array $object_bool;
    /**
     * @var array<string, float>
     */
    public array $object_float;
    /**
     * @var array<string, DocBlockArrayItemExtractorItem>
     */
    public array $object_object;
    /**
     * @var array<string, DocBlockArrayItemExtractorItem|null>
     */
    public array $object_object_nullable;
    /**
     * @var array<string, null|DocBlockArrayItemExtractorItem>
     */
    public array $object_object_nullable_reverse;
    /**
     * @var array<string, DocBlockArrayItemExtractorItem[]>
     */
    public array $object_object_array;

    // 紧凑写法（逗号后无空格）—— 此前因 explode(', ', ...) 解析失败触发 Undefined array key 1 warning
    /**
     * @var array<int,int>
     */
    public array $object_int_no_space;
    /**
     * @var array<string,string>
     */
    public array $object_string_no_space;
    /**
     * @var array<string,DocBlockArrayItemExtractorItem>
     */
    public array $object_object_no_space;
    // 无空格 + 嵌套（value 为数组）：验证 explode(',', ..., 2) 在嵌套下也正确切出 value
    /**
     * @var array<string,DocBlockArrayItemExtractorItem[]>
     */
    public array $object_object_array_no_space;
    // 无空格 + value 含内层逗号（array<k, array<...>>）：验证 limit=2 不会被内层逗号切错
    /**
     * @var array<int,array<string,int>>
     */
    public array $object_nested_map_no_space;
}
