<?php

namespace Tests\Fixtures\Swagger\Overwrite;

use WebmanTech\DTO\BaseDTO;

// 包含 static 属性的 DTO 类，用于测试 static 属性不被自动生成 Property
class ClassWithStaticProp extends BaseDTO
{
    public string $name;
    public static string $staticName = 'default';
}
