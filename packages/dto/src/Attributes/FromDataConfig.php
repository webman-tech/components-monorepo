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
        public bool $trim = false, // 对字符串值进行 trim 处理
        public bool $validatePropertiesAllWithBail = false, // 给每个属性都添加 bail 验证（验证失败时停止该字段的后续验证）
    )
    {
    }

    public static function createForRequestDTO(): self
    {
        $config = array_merge([
            'ignore_null' => false,
            'ignore_empty' => false,
            'trim' => false,
            'validate_properties_all_with_bail' => false,
        ], (array) ConfigHelper::get('dto.from_data_config.request', []));

        return new self(
            ignoreNull: $config['ignore_null'],
            ignoreEmpty: $config['ignore_empty'],
            trim: $config['trim'],
            validatePropertiesAllWithBail: $config['validate_properties_all_with_bail'],
        );
    }

    public static function createForBaseDTO(): self
    {
        $config = array_merge([
            'ignore_null' => false,
            'ignore_empty' => false,
            'trim' => false,
            'validate_properties_all_with_bail' => false,
        ], (array) ConfigHelper::get('dto.from_data_config.base', []));

        return new self(
            ignoreNull: $config['ignore_null'],
            ignoreEmpty: $config['ignore_empty'],
            trim: $config['trim'],
            validatePropertiesAllWithBail: $config['validate_properties_all_with_bail'],
        );
    }
}
