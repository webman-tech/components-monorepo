<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema]
class SchemaWithParent extends SchemaB
{
    #[OA\Property(type: 'string')]
    public string $childName;
}
