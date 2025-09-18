<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ToArrayConfig
{
    public function __construct(
        public ?array          $only = null, // 仅包含
        public ?array          $include = null, // 额外包含
        public ?array          $exclude = null, // 排除部分
        public ?bool           $ignoreNull = null, // 忽略值为 null 的
        public null|array|true $emptyArrayAsObject = null, // 空数组当做对象
    )
    {
    }
}
