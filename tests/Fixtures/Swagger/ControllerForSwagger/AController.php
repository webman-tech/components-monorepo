<?php

namespace Tests\Fixtures\Swagger\ControllerForSwagger;

use OpenApi\Attributes as OA;

class AController
{
    #[OA\Get(
        path: '/a/get',
    )]
    public function get()
    {
    }

    #[OA\Post(
        path: '/a/post',
    )]
    public function post()
    {
    }
}
