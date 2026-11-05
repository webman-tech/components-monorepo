<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Spec as OA;

#[OA\Tag(name: 'controller')]
class ControllerWithInfo
{
    #[OA\Operation\Get(
        path: '/get'
    )]
    public function get()
    {

    }

    #[OA\Operation\Post(
        path: '/post'
    )]
    public function post()
    {

    }

    #[OA\Operation\Get(
        path: '/get-more-tag',
        tags: ['more-tag']
    )]
    public function getMoreTag()
    {

    }

    #[OA\Operation\Get(
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
