<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ToArrayConfig
{
    public function __construct(
        public ?array          $only = null, // 仅包含
        public ?array          $include = null, // 额外包含
        public ?array          $exclude = null, // 排除部分
        public ?bool           $ignoreNull = null, // 忽略值为 null 的
        public null|array|true $emptyArrayAsObject = null, // 空数组当做对象
        public ?string         $singleKey = null, // 仅返回当前对象下的某个字段的值（可能不在是个数组了）
    )
    {
    }
}
