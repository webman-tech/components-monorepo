<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema;

use OpenApi\Attributes as OA;

#[OA\Schema]
class ExampleRequestWithMethodSchema
{
    #[OA\Property(description: 'body 参数', example: 'value', x: ['in' => 'body'])]
    public string $body;

    public function doSomething(): ExampleResponseSchema
    {
    }
}
