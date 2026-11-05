<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;

final class EmptyMapDefaults extends BaseDTO
{
    #[ValidationRules(object: true)]
    public array $extra = [];

    /**
     * @var array<string, string>
     */
    public array $names = [];

    #[ValidationRules(object: true)]
    public ?array $nullableMap = [];

    #[ValidationRules(object: true)]
    public ?array $nullMap = null;

    #[ValidationRules(object: true)]
    public array $populatedMap = ['key' => 'value'];

    /**
     * @var string[]
     */
    public array $list = [];

    #[OA\Property(schema: new OA\Schema(type: 'object', example: ['key' => 'example']))]
    public array $withExample = [];

    #[OA\Property(schema: new OA\Schema(type: 'array', items: new OA\Schema(type: 'object', default: [])))]
    public array $nested = [];
}
