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
     * @var array<string, DocBlockArrayItemExtractorItem[]>
     */
    public array $object_object_array;
}
