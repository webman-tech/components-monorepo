<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ControllerForValidationRulesOperationDescription
{
    #[OA\Post(
        path: '/post/validation-rules-multi-schema',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => [
                ValidationRulesRequestA::class,
                ValidationRulesRequestB::class,
            ],
        ],
    )]
    public function post()
    {
    }
}

#[OA\Schema]
class ValidationRulesRequestA extends BaseRequestDTO
{
    #[ValidationRules(required: true, string: true)]
    public string $name;
}

#[OA\Schema]
class ValidationRulesRequestB extends BaseRequestDTO
{
    #[ValidationRules(required: true, integer: true)]
    public int $age;
}
