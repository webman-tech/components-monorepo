<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema]
class SchemaNested
{
    #[OA\Property(type: 'string')]
    public string $nestedName;
    #[OA\Property]
    public SchemaB $nestedB;
}
