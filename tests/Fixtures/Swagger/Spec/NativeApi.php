<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

enum NativeStatus: string
{
    case Active = 'active';
}

#[OA\Schema]
final class NativeQuery
{
    /**
     * Record identifier
     *
     * @example 0
     */
    #[OA\Property]
    public int $id;

    #[OA\Property]
    public NativeStatus $status;
}

final class NativeController
{
    #[OA\Operation\Get(path: '/native', x: [SchemaConstants::X_SCHEMA_REQUEST => NativeQuery::class])]
    public function index(): void
    {
    }
}
