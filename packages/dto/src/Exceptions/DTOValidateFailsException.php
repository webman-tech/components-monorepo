<?php

namespace WebmanTech\DTO\Exceptions;

use Illuminate\Support\Arr;

class DTOValidateFailsException extends \Exception
{
    public function __construct(private readonly array $errors, ?\Throwable $previous = null)
    {
        parent::__construct('DTOValidateFailsException', previous: $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function first(): string
    {
        return Arr::first($this->errors);
    }
}
