<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use OpenApi\Spec as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Spec\SpecHelper;

final class RouteConfigDTO
{
    public function __construct(
        public string            $desc = '',
        public string            $method = '',
        public string            $path = '',
        public string            $controller = '',
        public string            $action = '',
        public null|string       $name = null,
        public null|string|array $middlewares = null,
    )
    {
    }

    /**
     * 将接口反射和路由扩展元数据转换为路由定义
     */
    public static function fromOperation(OA\Operation $operation): ?self
    {
        $reflector = $operation->getReflector();
        if (!$reflector instanceof \ReflectionMethod) {
            return null;
        }
        $method = $operation->method;
        if ($method === null || $method === 'trace' || $operation->path === null) {
            return null;
        }
        $summary = SpecHelper::value($operation->summary, '');
        $description = SpecHelper::value($operation->description, '');

        return new self(
            desc: $summary && $description ? "{$summary}({$description})" : $summary . $description,
            method: strtoupper($method),
            path: $operation->x[SchemaConstants::X_PATH] ?? $operation->path,
            controller: $operation->getClassName() ?? '',
            action: $reflector->getName(),
            name: $operation->x[SchemaConstants::X_NAME] ?? null,
            middlewares: $operation->x[SchemaConstants::X_MIDDLEWARE] ?? null,
        );
    }
}
