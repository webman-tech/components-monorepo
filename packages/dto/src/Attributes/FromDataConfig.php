<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;
use WebmanTech\DTO\Helper\ConfigHelper;

#[Attribute(Attribute::TARGET_CLASS)]
final class FromDataConfig
{
    public function __construct(
        public bool $ignoreNull = false, // 忽略值为 null 的
        public bool $ignoreEmpty = false, // 忽略值为空字符串的
    )
    {
    }

    public static function createForRequestDTO(): self
    {
        $config = array_merge([
            'ignore_null' => false,
            'ignore_empty' => false,
        ], (array) ConfigHelper::get('dto.from_data_config.request', []));

        return new self(
            ignoreNull: $config['ignore_null'],
            ignoreEmpty: $config['ignore_empty'],
        );
    }

    public static function createForBaseDTO(): self
    {
        $config = array_merge([
            'ignore_null' => false,
            'ignore_empty' => false,
        ], (array) ConfigHelper::get('dto.from_data_config.base', []));

        return new self(
            ignoreNull: $config['ignore_null'],
            ignoreEmpty: $config['ignore_empty'],
        );
    }
}
