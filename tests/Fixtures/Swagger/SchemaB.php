<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

#[OA\Schema]
class SchemaB
{
    public function __construct(
        #[OA\Property(schema: new OA\Schema(type: 'string'))]
        public string $name,
    )
    {
    }
}
