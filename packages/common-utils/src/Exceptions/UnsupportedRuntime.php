<?php

namespace WebmanTech\CommonUtils\Exceptions;

use Throwable;

final class UnsupportedRuntime extends \InvalidArgumentException
{
    public function __construct(string $message = "Unsupported runtime", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
