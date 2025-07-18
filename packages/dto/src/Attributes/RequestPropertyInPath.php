<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class RequestPropertyInPath extends RequestPropertyIn
{
    public function __construct(
        public ?string $name = null,
    )
    {
        parent::__construct(RequestPropertyInEnum::Path->value, name: $this->name);
    }
}
