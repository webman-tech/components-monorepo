<?php

namespace Tests\Fixtures\Swagger;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Spec as OA;

/**
 * 描述
 *
 * @property \Illuminate\Support\Carbon $created_at 创建时间
 */
#[OA\Schema]
abstract class SchemaEloquentAbsModel extends Model
{
}
