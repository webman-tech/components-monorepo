<?php

namespace Tests\Fixtures\Swagger;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

/**
 * 描述
 *
 * @property int $id (主键)
 * @property string $username 用户名
 * @property string|null $access_token Access Token
 * @property \Illuminate\Support\Carbon $created_at 创建时间
 *
 * others
 * @property-read SchemaEloquentModel $relations
 */
#[OA\Schema]
class SchemaEloquentModel extends Model
{
}
