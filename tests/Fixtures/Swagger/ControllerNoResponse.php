<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

class ControllerNoResponse
{
    #[OA\Operation\Get(
        path: '/get'
    )]
    public function get()
    {

    }

    public function post()
    {

    }
}
