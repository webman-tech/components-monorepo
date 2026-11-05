<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

#[OA\Schema]
class SchemaWithTrait
{
    use SchemaWithTraitTrait;

    #[OA\Property()]
    public string $childName;
}

trait SchemaWithTraitTrait
{
    #[OA\Property()]
    public string $traitName;
}
