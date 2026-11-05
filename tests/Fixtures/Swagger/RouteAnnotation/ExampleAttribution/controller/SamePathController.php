<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleAttribution\controller;

use OpenApi\Spec as OA;

class SamePathController
{
    #[OA\Operation\Get(
        path: '/same-path',
    )]
    public function get()
    {
    }

    #[OA\Operation\Post(
        path: '/same-path',
    )]
    public function post()
    {
    }

    #[OA\Operation\Put(
        path: '/same-path',
    )]
    public function put()
    {
    }

    #[OA\Operation\Delete(
        path: '/same-path',
    )]
    public function delete()
    {
    }
}
