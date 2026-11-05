<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Spec as OA;

abstract class CustomModelBase extends Model
{
    public $timestamps = false;
    public bool $runtimeFlag = false;
}

/**
 * @property int $id Model identifier
 */
#[OA\Schema]
final class CustomModel extends CustomModelBase
{
    #[OA\Property(schema: new OA\Schema(type: 'string'))]
    public string $documentedField;
}
