<?php

namespace WebmanTech\DTO\Processor;

use stdClass;

/**
 * 根据 ToArrayConfig 将指定空数组转为空对象
 */
final class ConvertEmptyArraysToObjects implements ToArrayProcessorInterface
{
    public function __invoke(ToArrayContext $context): void
    {
        if (!$context->config->emptyArrayAsObject || $context->config->singleKey || !is_array($context->data)) {
            return;
        }

        foreach ($context->data as $property => $value) {
            if ($value !== [] || !in_array($property, $context->arrayProperties, true)) {
                continue;
            }
            if (
                $context->config->emptyArrayAsObject === true
                || in_array($property, $context->config->emptyArrayAsObject, true)
            ) {
                $context->data[$property] = new stdClass();
            }
        }
    }
}
