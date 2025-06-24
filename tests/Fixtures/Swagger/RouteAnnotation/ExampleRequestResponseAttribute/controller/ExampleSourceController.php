<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\controller;

use OpenApi\Attributes as OA;
use support\Request;
use support\Response;
use Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema\ExampleRequestBodySchema;
use Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema\ExampleRequestSchema;
use Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema\ExampleRequestWithMethodSchema;
use Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema\ExampleResponseBodySchema;
use Tests\Fixtures\Swagger\RouteAnnotation\ExampleRequestResponseAttribute\schema\ExampleResponseSchema;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ExampleSourceController
{
    #[OA\Post(
        path: '/example-get',
        summary: '样例 get',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ExampleRequestSchema::class,
            SchemaConstants::X_SCHEMA_RESPONSE => ExampleResponseSchema::class,
        ],
    )]
    public function get(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Post(
        path: '/example-post',
        summary: '样例 post',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ExampleRequestSchema::class,
            SchemaConstants::X_SCHEMA_RESPONSE => ExampleResponseSchema::class,
        ],
    )]
    public function post(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Post(
        path: '/example-post-body',
        summary: '样例 body',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ExampleRequestBodySchema::class,
            SchemaConstants::X_SCHEMA_RESPONSE => ExampleResponseBodySchema::class,
        ],
    )]
    public function postBody(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Post(
        path: '/example-request-auto-response',
        summary: '样例 body',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ExampleRequestWithMethodSchema::class . '@doSomething',
        ],
    )]
    public function postBodyAutoResponse(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }
}
