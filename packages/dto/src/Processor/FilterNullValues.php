<?php

namespace WebmanTech\DTO\Processor;

/**
 * 根据 ToArrayConfig 过滤 null
 */
final class FilterNullValues implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        if (!$context->config->ignoreNull || $context->config->singleKey || !is_array($context->data)) {
            return;
        }

        foreach ($context->data as $property => $value) {
            if (is_array($value)) {
                $context->data[$property] = array_filter($value, static fn(mixed $item): bool => $item !== null);
            } elseif ($value === null) {
                unset($context->data[$property]);
            }
        }
    }
}
