<?php

namespace WebmanTech\DTO\Exceptions;

use Illuminate\Support\Arr;

final class DTOValidateException extends DTOException
{
    public function __construct(private readonly array $errors, ?\Throwable $previous = null)
    {
        parent::__construct('DTOValidateException: ' . $this->first(), previous: $previous);
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
