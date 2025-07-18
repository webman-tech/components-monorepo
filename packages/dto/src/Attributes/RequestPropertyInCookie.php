<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class RequestPropertyInCookie extends RequestPropertyIn
{
    public function __construct(
        public ?string $name = null,
    )
    {
        parent::__construct(RequestPropertyInEnum::Cookie->value, name: $this->name);
    }
}
