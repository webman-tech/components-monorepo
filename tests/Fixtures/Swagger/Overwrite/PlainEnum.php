<?php

namespace Tests\Fixtures\Swagger\Overwrite;

// 故意不添加 #[Schema] 注解，用于测试 AttributeAnnotationFactory 的自动生成能力
enum PlainEnum: string
{
    case A = 'a';
    case B = 'b';
}
