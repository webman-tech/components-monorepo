<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema;

use OpenApi\Attributes as OA;

#[OA\Schema]
class ExampleResponseSchema
{
    #[OA\Property(description: 'header 参数', example: 'header value', x: ['in' => 'header'])]
    public string $headerKey;
    #[OA\Property(description: '参数', example: 'value')]
    public int $key;
}
