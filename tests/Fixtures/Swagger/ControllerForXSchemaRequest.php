<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ControllerForXSchemaRequest
{
    #[OA\Get(
        path: '/get/schema',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class,
        ],
    )]
    public function get()
    {
    }

    #[OA\Get(
        path: '/get/schema-multi',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => [
                ControllerForXSchemaRequestSchemaA::class,
                ControllerForXSchemaRequestSchemaB::class,
            ],
        ],
    )]
    public function get2()
    {
    }

    #[OA\Post(
        path: '/post/schema',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class,
        ],
    )]
    public function post()
    {
    }

    #[OA\Get(
        path: '/get/schema-with-at',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class . '@get'
        ]
    )]
    public function get3()
    {

    }

    #[OA\Get(
        path: '/get/schema-with-at-already-has-response',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class . '@get',
            SchemaConstants::X_SCHEMA_RESPONSE => ControllerForXSchemaRequestSchemaA::class,
        ]
    )]
    public function get4()
    {

    }

    #[OA\Get(
        path: '/get/schema-with-at-union-type',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaD::class . '@get',
        ]
    )]
    public function get5()
    {

    }

    #[OA\Post(
        path: '/post/schema-x-in',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaC::class,
        ],
    )]
    public function post2()
    {
    }
}

#[OA\Schema]
class ControllerForXSchemaRequestSchemaA
{
    #[OA\Property]
    public string $name;

    public function get(): ControllerForXSchemaRequestSchemaB
    {
    }
}

#[OA\Schema]
class ControllerForXSchemaRequestSchemaB
{
    #[OA\Property]
    public int $age;
}

#[OA\Schema]
class ControllerForXSchemaRequestSchemaC
{
    #[OA\Property(x: ['in' => 'query'])]
    public string $query;

    #[OA\Property(x: ['in' => 'header'])]
    public string $header;
}


#[OA\Schema]
class ControllerForXSchemaRequestSchemaD
{
    #[OA\Property]
    public string $name;

    public function get(): ControllerForXSchemaRequestSchemaB|ControllerForXSchemaRequestSchemaC
    {
    }
}
