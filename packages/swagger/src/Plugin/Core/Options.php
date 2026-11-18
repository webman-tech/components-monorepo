<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core;

/**
 * Core 插件的配置；使用命名参数校验配置项及类型
 *
 * @internal
 */
final readonly class Options
{
    public function __construct(
        public array $autoLoadSchemaClasses = [], // 自动生成 Schema 的基类
        public bool $cleanUnusedComponents = false, // 是否移除未被引用的组件
        public bool $sortComponents = true, // 是否对组件和路径排序
        public bool $sortSchemas = true, // 是否对 Schema 组件排序
        public bool $sortPaths = false, // 是否对 Path 操作排序
        public ?string $responseLayoutClass = null, // 响应包装类，null 表示不启用包装
        public string $responseLayoutDataCode = 'data', // 响应包装中承载数据的字段名
        public bool $routeMetadata = false, // 是否保留路由注册所需的内部元数据
    )
    {
    }
}
