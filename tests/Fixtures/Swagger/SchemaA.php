<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema]
class SchemaA
{
    public function __construct(
        #[OA\Property(type: 'string')]
        public string $name,
    )
    {
    }
}
