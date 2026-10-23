<?php

namespace WebmanTech\DTO\Processor;

use Closure;
use DateTime;
use WebmanTech\DTO\Attributes\ToArrayConfig;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Reflection\ReflectionClassReader;

/**
 * toArray 处理器共享的上下文
 */
final class ToArrayContext
{
    private ?string $dateTimeFormat = null;

    /**
     * @param Closure(string): mixed $propertyReader
     * @param Closure(): string $dateTimeFormatResolver
     * @param list<string> $properties
     * @param list<string> $arrayProperties
     */
    public function __construct(
        public readonly BaseDTO $dto,
        public readonly ToArrayConfig $config,
        public readonly ReflectionClassReader $reflection,
        private readonly Closure $propertyReader,
        private readonly Closure $dateTimeFormatResolver,
        public array $properties = [],
        public array $arrayProperties = [],
        public mixed $data = [],
    )
    {
    }

    public function readProperty(string $property): mixed
    {
        return ($this->propertyReader)($property);
    }

    public function formatDateTime(DateTime $dateTime): string
    {
        $this->dateTimeFormat ??= ($this->dateTimeFormatResolver)();

        return $dateTime->format($this->dateTimeFormat);
    }
}
