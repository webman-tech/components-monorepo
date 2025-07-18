<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;

/**
 * 定义属性在 request 中的来源
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RequestPropertyIn
{
    public function __construct(
        /**
         * 来源
         * @see RequestPropertyInEnum
         */
        private string $in,
        /**
         * 属性名，为 null 时自动取属性名
         */
        public ?string $name = null,
    )
    {
    }

    public function getInEnum(): RequestPropertyInEnum
    {
        return RequestPropertyInEnum::from($this->in);
    }
}
