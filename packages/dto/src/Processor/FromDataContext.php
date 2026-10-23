<?php

namespace WebmanTech\DTO\Processor;

use Closure;
use WebmanTech\DTO\Attributes\FromDataConfig;

/**
 * fromData 处理管线的共享上下文
 *
 * 各 Processor 按序执行，通过改写 data 影响后续阶段，
 * 最终 data 作为实例化（newInstanceByData）的输入
 */
final class FromDataContext
{
    /**
     * @param array<string, mixed> $data 当前数据，可被各 Processor 改写
     * @param class-string<\WebmanTech\DTO\BaseDTO> $dtoClass
     * @param Closure(array<string, mixed>): array<string, mixed> $validator
     */
    public function __construct(
        public array $data,
        public readonly string $dtoClass,
        public readonly ?FromDataConfig $config,
        public readonly bool $validate,
        private readonly Closure $validator,
    )
    {
    }

    public function runValidation(): void
    {
        if ($this->validate) {
            $this->data = ($this->validator)($this->data);
        }
    }
}
