<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 嵌套用，带 required
 */
#[OA\Schema(required: ['int'])]
class SchemaNestedHasRequired extends BaseSchema
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
}
