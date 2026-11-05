<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\RouteAnnotation;

use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;
use WebmanTech\Swagger\Spec\DocumentBuilder;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 从保留反射信息的 Spec 中读取控制器和路由元数据
 */
final class Reader
{
    /**
     * 通过 swagger 文档读取路由信息
     *
     * 传入配置文件时复用同一份解析配置，避免路由与文档生成的结果不一致
     *
     * @param ConfigOpenapiDocDTO|iterable|null $config 文档配置，或仅扫描源
     * @return array<string, RouteConfigDTO>
     */
    public function getData(ConfigOpenapiDocDTO|iterable|null $config = null): array
    {
        $config = $config instanceof ConfigOpenapiDocDTO
            ? clone $config
            : new ConfigOpenapiDocDTO(scan_path: $config ?? []);
        /*
         * 路由注册发生在启动期，可能没有请求上下文，不执行应用自定义的 builder_modify
         */
        $config->builder_modify = null;

        $result = (new DocumentBuilder($config))->create(documentDefaults: false, routeMetadata: true)->build();
        $data = [];
        foreach ($result->specification()->operations ?? [] as $operation) {
            $reflector = $operation->getReflector();
            if (!$reflector instanceof \ReflectionMethod) {
                continue;
            }
            $method = $operation->method;
            if ($method === null || $method === 'trace' || $operation->path === null) {
                continue;
            }
            $summary = SpecHelper::value($operation->summary, '');
            $description = SpecHelper::value($operation->description, '');
            $route = new RouteConfigDTO(
                desc: $summary && $description ? "{$summary}({$description})" : $summary . $description,
                method: strtoupper($method),
                path: $operation->x[SchemaConstants::X_PATH] ?? $operation->path,
                controller: $operation->getClassName() ?? '',
                action: $reflector->getName(),
                name: $operation->x[SchemaConstants::X_NAME] ?? null,
                middlewares: $operation->x[SchemaConstants::X_MIDDLEWARE] ?? null,
            );
            $data[$route->method . ':' . $route->path] = $route;
        }

        return $data;
    }
}
