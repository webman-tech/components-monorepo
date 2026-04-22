<?php

namespace Tests\Fixtures\Swagger\Overwrite;

// Unit Enum（无 backing type），用于测试 AttributeAnnotationFactory 对无值枚举的处理
enum PlainUnitEnum
{
    case X;
    case Y;
}
