<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\WebmanRoute;

/**
 * WebmanRoute 插件的配置；使用命名参数校验配置项及类型
 *
 * @internal
 */
final readonly class Options
{
    public function __construct(
        public bool $preserveMetadata = false, // 是否保留路由注册所需的内部元数据
    )
    {
    }
}
