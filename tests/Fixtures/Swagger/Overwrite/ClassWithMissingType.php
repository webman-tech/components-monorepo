<?php

namespace Tests\Fixtures\Swagger\Overwrite;

// 属性类型引用了一个不存在的类，用于触发 ReflectionAnalyser 的 Class not found 错误处理
// PHP 不会在加载时解析类型提示，所以此文件可以正常加载
class ClassWithMissingType extends \WebmanTech\DTO\BaseDTO
{
    public \NonExistent\SomeClass $missing;
}
