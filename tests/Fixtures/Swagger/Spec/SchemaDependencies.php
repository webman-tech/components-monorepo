<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\Swagger\DTO\SchemaConstants as X;

final class DependencyController
{
    #[OA\Operation\Get(
        path: '/dependencies',
        x: [X::X_SCHEMA_REQUEST => DependencyQuery::class . '@handle'],
    )]
    public function query(): void
    {
    }
}

final class DependencyQuery extends BaseRequestDTO
{
    public DependencyState $state;

    public function handle(): DependencyResult|DependencyAlternative
    {
        return new DependencyResult();
    }
}

enum DependencyState: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}

final class DependencyResult extends BaseResponseDTO
{
    public DependencyDetails $details;
}

final class DependencyAlternative extends BaseResponseDTO
{
    public string $reason;
}

final class DependencyDetails extends BaseDTO
{
    public string $name;
}

final class DependencyLayout extends BaseResponseDTO
{
    public int $code;
}
