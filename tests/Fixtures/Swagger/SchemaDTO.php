<?php

namespace Tests\Fixtures\Swagger;

use DateTime;
use OpenApi\Attributes as OA;
use Webman\Http\UploadFile;
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
    public DateTime $date;

    #[OA\Property]
    public UploadFile $file;

    #[OA\Property]
    public UploadFile|string $fileOrString;

    #[OA\Property]
    #[ValidationRules(arrayItem: new ValidationRules(string: true))]
    public array $stringList;

    #[OA\Property]
    #[ValidationRules(arrayItem: new ValidationRules(string: true), object: true)]
    public array $map;

    #[OA\Property]
    #[RequestPropertyInHeader]
    public ?string $hasXin = null;

    #[OA\Property]
    public array|string|int $unionTypes;
}

#[OA\Schema]
class SchemaDTOChild extends BaseDTO
{
    #[OA\Property]
    #[ValidationRules(required: true, string: true, minLength: 5)]
    public $name;
}
