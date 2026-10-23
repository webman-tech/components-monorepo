<?php

namespace WebmanTech\DTO\Exceptions;

final class DTOProcessorException extends DTOException
{
    public function __construct(
        public readonly string $processor,
        public readonly string|null $fieldName = null,
        ?\Throwable $previous = null,
    )
    {
        parent::__construct(
            $this->fieldName === null
                ? "DTOProcessorException: {$this->processor} failed"
                : "DTOProcessorException: {$this->processor} process field {$this->fieldName} failed",
            previous: $previous,
        );
    }
}
