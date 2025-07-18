<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class ResponsePropertyInHeader extends RequestPropertyIn
{
    public function __construct(
        public ?string $name = null,
    )
    {
        parent::__construct(RequestPropertyInEnum::Header->value, name: $this->name);
    }
}
