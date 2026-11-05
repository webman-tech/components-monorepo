<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

#[OA\Schema]
class SchemaWithParent extends SchemaB
{
    #[OA\Property(schema: new OA\Schema(type: 'string'))]
    public string $childName;
}
