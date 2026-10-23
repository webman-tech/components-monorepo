<?php

namespace WebmanTech\DTO\Processor;

/**
 * 将 singleKey 配置的字段值作为最终结果
 */
final class ExtractSingleKey implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        $singleKey = $context->config->singleKey;
        if (!$singleKey || !is_array($context->data)) {
            return;
        }

        $context->data = $context->data[$singleKey] ?? null;
    }
}
