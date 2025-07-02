<?php

namespace Tests\Fixtures\Swagger\RouteAnnotation\ExampleAttribution\controller;

use OpenApi\Attributes as OA;

class SamePathController
{
    #[OA\Get(
        path: '/same-path',
    )]
    public function get()
    {
    }

    #[OA\Post(
        path: '/same-path',
    )]
    public function post()
    {
    }

    #[OA\Put(
        path: '/same-path',
    )]
    public function put()
    {
    }

    #[OA\Delete(
        path: '/same-path',
    )]
    public function delete()
    {
    }
}
