<?php

namespace WebmanTech\DTO\Processor;

/**
 * fromData 数据处理阶段
 */
interface ProcessorInterface
{
    public function __invoke(FromDataContext $context): void;
}
