<?php

namespace Tests\Fixtures\Swagger;

use WebmanTech\Swagger\DTO\SchemaConstants;
use OpenApi\Spec as OA;

class ControllerForXSchemaRequest
{
    #[OA\Operation\Get(
        path: '/get/schema',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class,
        ],
    )]
    public function get()
    {
    }

    #[OA\Operation\Get(
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

    #[OA\Operation\Post(
        path: '/post/schema',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class,
        ],
    )]
    public function post()
    {
    }

    #[OA\Operation\Get(
        path: '/get/schema-with-at',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class . '@get'
        ]
    )]
    public function get3()
    {

    }

    #[OA\Operation\Get(
        path: '/get/schema-with-at-already-has-response',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaA::class . '@get',
            SchemaConstants::X_SCHEMA_RESPONSE => ControllerForXSchemaRequestSchemaA::class,
        ]
    )]
    public function get4()
    {

    }

    #[OA\Operation\Get(
        path: '/get/schema-with-at-union-type',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaD::class . '@get',
        ]
    )]
    public function get5()
    {

    }

    #[OA\Operation\Post(
        path: '/post/schema-x-in',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaC::class,
        ],
    )]
    public function post2()
    {
    }

    #[OA\Operation\Get(
        path: '/get/schema-with-at-no-return-type',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestSchemaE::class . '@handle',
        ],
    )]
    public function get6()
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

    #[OA\Property(schema: new OA\Schema(type: ['boolean', 'null']), x: ['in' => 'query'])]
    public ?bool $nullableFlag = null;

    #[OA\Property(schema: new OA\Schema(type: 'boolean'), x: ['in' => 'query'])]
    public bool $flag = false;
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

#[OA\Schema]
class ControllerForXSchemaRequestSchemaE
{
    #[OA\Property]
    public string $name;

    public function handle()
    {
    }
}
