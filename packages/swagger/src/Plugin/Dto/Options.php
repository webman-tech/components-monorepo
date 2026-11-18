<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Dto;

/**
 * Dto 插件的配置；使用命名参数校验配置项及类型
 *
 * @internal
 */
final readonly class Options
{
    public function __construct(
        public bool $appendValidationRules = true, // 是否将 DTO 验证规则追加到 Operation 描述
    )
    {
    }
}
