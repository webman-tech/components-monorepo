<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

#[OA\Schema]
class SchemaNested
{
    #[OA\Property(schema: new OA\Schema(type: 'string'))]
    public string $nestedName;
    #[OA\Property]
    public SchemaB $nestedB;
}
