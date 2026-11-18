<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\NativeEnum;

/**
 * NativeEnum 插件的配置；使用命名参数校验配置项及类型
 *
 * @internal
 */
final readonly class Options
{
    public function __construct(
        public bool $descriptionEnabled = true, // 是否生成 Enum case 描述
        public string $descriptionMethod = 'description', // 读取 Enum case 描述的方法名
    )
    {
    }
}
