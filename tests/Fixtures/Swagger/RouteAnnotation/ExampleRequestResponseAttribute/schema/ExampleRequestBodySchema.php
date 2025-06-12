<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema;

use OpenApi\Attributes as OA;

#[OA\Schema]
class ExampleRequestBodySchema
{
    #[OA\Property(description: 'body 参数', example: 'value', x: ['in' => 'body'])]
    public string $body;
}
