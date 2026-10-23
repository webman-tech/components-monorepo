<?php

namespace WebmanTech\DTO\Processor;

/**
 * 数据清洗：trim / ignoreNull / ignoreEmpty（由 FromDataConfig 声明）
 */
final class SanitizeData implements ProcessorInterface
{
    public function __invoke(FromDataContext $context): void
    {
        $config = $context->config;
        if (!$config) {
            return;
        }

        if ($config->trim) {
            $context->data = array_map(static function ($value) {
                return is_string($value) ? trim($value) : $value;
            }, $context->data);
        }

        if ($config->ignoreNull || $config->ignoreEmpty) {
            $context->data = array_filter($context->data, static function ($value) use ($config) {
                if ($config->ignoreNull && $value === null) {
                    return false;
                }
                if ($config->ignoreEmpty && $value === '') {
                    return false;
                }
                return true;
            });
        }
    }
}
