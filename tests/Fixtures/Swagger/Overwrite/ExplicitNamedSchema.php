<?php

namespace Tests\Fixtures\Swagger\Overwrite;

use OpenApi\Attributes as OA;

// 显式指定了 schema 名称，用于测试 schema name formatting 不覆盖手动命名的场景
#[OA\Schema(schema: 'ExplicitlyNamed')]
class ExplicitNamedSchema
{
    #[OA\Property(type: 'string')]
    public string $name;
}
