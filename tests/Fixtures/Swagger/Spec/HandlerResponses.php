<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\Swagger\DTO\SchemaConstants as X;

final class HandlerResponsesController
{
    #[OA\Operation\Post(
        path: '/handler/string',
        x: [X::X_SCHEMA_REQUEST => ScalarHandlerForm::class . '@handle'],
    )]
    public function scalar(): void
    {
    }

    #[OA\Operation\Post(
        path: '/handler/union',
        x: [X::X_SCHEMA_REQUEST => ScalarHandlerForm::class . '@union'],
    )]
    public function union(): void
    {
    }
}

final class ScalarHandlerForm extends BaseRequestDTO
{
    public function handle(): string
    {
        return 'success';
    }

    public function union(): string|HandlerResult
    {
        return 'success';
    }
}

final class HandlerResult extends BaseResponseDTO
{
    public string $value;
}
