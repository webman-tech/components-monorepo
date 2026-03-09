<?php

namespace Tests\Fixtures\Swagger\ExampleDiscriminator;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

#[OA\Tag(name: 'orders', description: '订单管理')]
final class OrderController
{
    #[OA\Post(
        path: '/orders',
        summary: 'Create a new order',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => CreateOrderForm::class . '@handle',
        ]
    )]
    public function create()
    {
        return CreateOrderForm::fromRequest()
            ->handle()
            ->toResponse();
    }
}
