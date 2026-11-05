<?php

namespace Tests\Fixtures\Swagger\ExampleDTO;

use WebmanTech\Swagger\DTO\SchemaConstants;
use OpenApi\Spec as OA;

#[OA\Tag(name: 'users', description: '用户管理')]
final class UserController
{
    #[OA\Operation\Post(
        path: '/users',
        summary: 'Create a new user',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => UserCreateForm::class . '@handle',
        ]
    )]
    public function create()
    {
        return UserCreateForm::fromRequest()
            ->handle()
            ->toResponse();
    }
}
