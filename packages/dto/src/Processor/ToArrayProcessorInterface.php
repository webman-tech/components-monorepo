<?php

namespace WebmanTech\DTO\Processor;

/**
 * toArray 数据处理阶段
 */
interface ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void;
}
