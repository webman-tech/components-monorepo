<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

class ControllerNoResponse
{
    #[OA\Get(
        path: '/get'
    )]
    public function get()
    {

    }

    public function post()
    {

    }
}
