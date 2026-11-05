<?php

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;

/*
 * 显式指定了 schema 名称，用于测试 schema name formatting 不覆盖手动命名的场景
 */
#[OA\Schema(component: 'ExplicitlyNamed')]
class ExplicitNamedSchema
{
    #[OA\Property(schema: new OA\Schema(type: 'string'))]
    public string $name;
}
