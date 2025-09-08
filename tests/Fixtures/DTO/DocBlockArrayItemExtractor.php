<?php

namespace Tests\Fixtures\DTO;

use Tests\Fixtures\DTO\Dir\DocBlockArrayItemExtractorDirItem2;

final class DocBlockArrayItemExtractor
{
    /**
     * @var array|string[]
     */
    public array $array_string;
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
}
