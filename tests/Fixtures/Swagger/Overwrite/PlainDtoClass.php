<?php

namespace Tests\Fixtures\Swagger\Overwrite;

use WebmanTech\DTO\BaseDTO;

// 故意不添加 #[Schema] 和 #[Property] 注解，用于测试 AttributeAnnotationFactory 的自动生成能力
class PlainDtoClass extends BaseDTO
{
    public string $name;
    public int $age;
    protected string $secret;
    private float $hidden;
}
