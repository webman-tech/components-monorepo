<?php

namespace Tests\Fixtures\Swagger\ControllerForSwagger;

use OpenApi\Spec as OA;

class AController
{
    #[OA\Operation\Get(
        path: '/a/get',
    )]
    public function get()
    {
    }

    #[OA\Operation\Post(
        path: '/a/post',
    )]
    public function post()
    {
    }
}
