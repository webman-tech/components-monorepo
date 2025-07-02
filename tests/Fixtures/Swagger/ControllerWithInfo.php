<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'controller')]
class ControllerWithInfo
{
    #[OA\Get(
        path: '/get'
    )]
    public function get()
    {

    }

    #[OA\Post(
        path: '/post'
    )]
    public function post()
    {

    }
}
