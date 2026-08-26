<?php

namespace Tests\Fixtures\Swagger;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

/**
 * @property int $id
 * @property string $username
 * @property string|null $access_token
 * @property \Illuminate\Support\Carbon $created_at 创建时间
 */
#[OA\Schema]
class SchemaEloquentNoDescModel extends Model
{
}
