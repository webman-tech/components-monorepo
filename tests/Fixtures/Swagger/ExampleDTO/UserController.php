<?php

namespace Tests\Fixtures\Swagger\ExampleDTO;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

#[OA\Tag(name: 'users', description: '用户管理')]
final class UserController
{
    #[OA\Post(
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
