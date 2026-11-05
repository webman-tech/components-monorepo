<?php

namespace Tests\Fixtures\Swagger\ControllerForSwagger;

use OpenApi\Spec as OA;

class BController
{
    #[OA\Operation\Get(
        path: '/b',
    )]
    public function get()
    {
    }

    #[OA\Operation\Post(
        path: '/b',
    )]
    public function post()
    {
    }
}
