<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema;

use OpenApi\Attributes as OA;

#[OA\Schema]
class ExampleRequestSchema
{
    #[OA\Property(description: 'path 参数', example: 'path value', x: ['in' => 'path'])]
    public string $pathKey;
    #[OA\Property(description: 'query 参数', example: 'query value', x: ['in' => 'query'])]
    public string $queryKey;
    #[OA\Property(description: 'header 参数', example: 'header value', x: ['in' => 'header'])]
    public string $headerKey;
    #[OA\Property(description: '参数', example: 'value')]
    public int $key;
}
