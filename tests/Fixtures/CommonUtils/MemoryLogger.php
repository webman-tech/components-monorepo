<?php

namespace Tests\Fixtures\CommonUtils;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class MemoryLogger implements LoggerInterface
{
    use LoggerTrait;

    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function flush(): void
    {
        $this->logs = [];
    }

    public function getAll(): array
    {
        return $this->logs;
    }
}
