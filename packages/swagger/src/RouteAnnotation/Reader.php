<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\RouteAnnotation;

use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;
use WebmanTech\Swagger\Spec\DocumentBuilder;

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
            $route = $operation->getMeta(RouteConfigDTO::class);
            if (!$route instanceof RouteConfigDTO) {
                $route = RouteConfigDTO::fromOperation($operation);
            }
            if ($route === null) {
                continue;
            }
            $data[$route->method . ':' . $route->path] = $route;
        }

        return $data;
    }
}
