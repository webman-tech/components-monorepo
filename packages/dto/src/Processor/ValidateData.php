<?php

namespace WebmanTech\DTO\Processor;

/**
 * 字段验证（ValidationRules 注解驱动）
 *
 * 验证后的数据替换 context->data（只保留验证过的字段）
 */
final class ValidateData implements ProcessorInterface
{
    public function __invoke(FromDataContext $context): void
    {
        $context->runValidation();
    }
}
