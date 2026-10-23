<?php

namespace WebmanTech\DTO\Processor;

/**
 * 根据 ToArrayConfig 选择需要输出的属性
 */
final class SelectProperties implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        if ($context->config->singleKey) {
            $context->properties = [$context->config->singleKey];
            return;
        }

        if ($context->config->only) {
            $context->properties = array_values($context->config->only);
            return;
        }

        $properties = $context->reflection->getPropertyNameList();
        if ($context->config->include) {
            $properties = array_merge($properties, $context->config->include);
        }
        if ($context->config->exclude) {
            $properties = array_diff($properties, $context->config->exclude);
        }

        $context->properties = array_values($properties);
    }
}
