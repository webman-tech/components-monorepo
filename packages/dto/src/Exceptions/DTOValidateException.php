<?php

namespace WebmanTech\DTO\Exceptions;

use WebmanTech\DTO\Helper\ArrayHelper;

final class DTOValidateException extends DTOException
{
    public function __construct(private readonly array $errors, ?\Throwable $previous = null)
    {
        parent::__construct('DTOValidateException: ' . $this->first(), previous: $previous);
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 第一个错误
     */
    public function first(): string
    {
        return ArrayHelper::first(ArrayHelper::wrap(ArrayHelper::first($this->errors, '')));
    }
}
