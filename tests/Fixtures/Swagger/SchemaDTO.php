<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;

#[OA\Schema]
class SchemaDTO extends BaseDTO
{
    #[OA\Property]
    #[ValidationRules(required: true, string: true, minLength: 5)]
    public $name;

    #[OA\Property]
    #[ValidationRules(min: 1, max: 100)]
    public int $age;

    #[OA\Property]
    public array $arrayEmptyType;

    #[OA\Property]
    #[ValidationRules(arrayItem: SchemaDTOChild::class)]
    public array $children;

    #[OA\Property]
    public SchemaDTOChild $child;

    #[OA\Property]
    #[ValidationRules(arrayItem: new ValidationRules(string: true))]
    public array $stringList;

    #[OA\Property]
    #[RequestPropertyInHeader]
    public ?string $hasXin = null;
}

class SchemaDTOChild extends BaseDTO
{
    #[OA\Property]
    #[ValidationRules(required: true, string: true, minLength: 5)]
    public $name;
}
