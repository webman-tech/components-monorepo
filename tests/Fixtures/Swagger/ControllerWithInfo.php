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

    #[OA\Get(
        path: '/get-more-tag',
        tags: ['more-tag']
    )]
    public function getMoreTag()
    {

    }

    #[OA\Get(
        path: '/get-skip-class',
        tags: [
            '--class-skip',
            'only-tag',
        ]
    )]
    public function getSkipClass()
    {

    }
}
