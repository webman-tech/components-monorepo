<?php

namespace Tests\Fixtures\Swagger\ControllerForSwagger;

use OpenApi\Attributes as OA;

class BController
{
    #[OA\Get(
        path: '/b',
    )]
    public function get()
    {
    }

    #[OA\Post(
        path: '/b',
    )]
    public function post()
    {
    }
}
