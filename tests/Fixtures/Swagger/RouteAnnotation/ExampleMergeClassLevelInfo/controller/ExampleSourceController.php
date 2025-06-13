<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleMergeClassLevelInfo\controller;

use OpenApi\Attributes as OA;
use support\Request;
use support\Response;

#[OA\Tag(name: 'crud', description: 'crud 例子')]
class ExampleSourceController
{
    #[OA\Get(
        path: '/crud',
        summary: '列表',
    )]
    public function index(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }
}
