<?php

namespace WebmanTech\CommonUtils\Json;

final class Expression implements \Stringable
{
    public function __construct(public string $expression)
    {
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
