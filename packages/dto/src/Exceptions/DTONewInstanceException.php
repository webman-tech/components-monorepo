<?php

namespace WebmanTech\DTO\Exceptions;

final class DTONewInstanceException extends DTOException
{
    public function __construct(private readonly string $className, ?\Throwable $previous = null)
    {
        parent::__construct("new {$className} failed", 0, $previous);
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
