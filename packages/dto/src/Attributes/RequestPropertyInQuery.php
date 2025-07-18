<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class RequestPropertyInQuery extends RequestPropertyIn
{
    public function __construct(
        public ?string $name = null,
    )
    {
        parent::__construct(RequestPropertyInEnum::Query->value, name: $this->name);
    }
}
