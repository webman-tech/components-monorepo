<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ControllerForXSchemaResponse
{
    #[OA\Get(
        path: '/get/schema',
        x: [
            SchemaConstants::X_SCHEMA_RESPONSE => ControllerForXSchemaResponseSchemaA::class,
        ],
    )]
    public function get()
    {
    }

    #[OA\Get(
        path: '/get/schema-multi',
        x: [
            SchemaConstants::X_SCHEMA_RESPONSE => [
                ControllerForXSchemaResponseSchemaA::class,
                ControllerForXSchemaResponseSchemaB::class,
            ],
        ],
    )]
    public function get2()
    {
    }

    #[OA\Get(
        path: '/get/schema-status-code',
        x: [
            SchemaConstants::X_SCHEMA_RESPONSE => [
                200 => ControllerForXSchemaResponseSchemaA::class,
                201 => ControllerForXSchemaResponseSchemaB::class,
            ],
        ],
    )]
    public function get3()
    {
    }

    #[OA\Get(
        path: '/get/schema-status-code-multi',
        x: [
            SchemaConstants::X_SCHEMA_RESPONSE => [
                200 => [
                    ControllerForXSchemaResponseSchemaA::class,
                    ControllerForXSchemaResponseSchemaB::class,
                ],
                201 => ControllerForXSchemaResponseSchemaB::class,
            ],
        ],
    )]
    public function get4()
    {
    }

    #[OA\Post(
        path: '/post/schema-x-in',
        x: [
            SchemaConstants::X_SCHEMA_RESPONSE => ControllerForXSchemaResponseSchemaC::class,
        ],
    )]
    public function post2()
    {
    }
}

#[OA\Schema]
class ControllerForXSchemaResponseSchemaA
{
    #[OA\Property]
    public string $name;
}

#[OA\Schema]
class ControllerForXSchemaResponseSchemaB
{
    #[OA\Property]
    public int $age;
}

#[OA\Schema]
class ControllerForXSchemaResponseSchemaC
{
    #[OA\Property(x: ['in' => 'header'])]
    public string $header;
}
