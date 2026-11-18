<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core;

use OpenApi\Contracts\RefInterface;
use OpenApi\Spec as OA;
use OpenApi\Spec\Concerns\RefTrait;

/**
 * 将 XSchema 中的类依赖暴露给官方 Resolver，不参与最终文档输出
 */
final class SchemaDependency extends OA\Attachable implements RefInterface
{
    use RefTrait;

    public string|OA\Schema\Ref|null $ref;

    public function __construct(string $ref, ?string $method = null)
    {
        parent::__construct();
        $this->ref = $ref;

        if ($method === null) {
            return;
        }
        /*
         * handle 的响应类型同样需要在依赖解析前声明，支持联合返回类型。
         */
        $handler = new \ReflectionMethod($ref, $method);
        $return = $handler->getReturnType();
        $types = $return instanceof \ReflectionUnionType ? $return->getTypes() : [$return];
        foreach ($types as $type) {
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $class = in_array($type->getName(), ['self', 'static'], true) ? $ref : $type->getName();
                $this->attachables[] = new self($class);
            }
        }
    }

    /**
     * 从类名、Class@method 和状态码映射中提取依赖，忽略描述等非类字符串
     *
     * @return list<self>
     */
    public static function from(mixed $value): array
    {
        if (is_array($value)) {
            $dependencies = [];
            foreach ($value as $item) {
                array_push($dependencies, ...self::from($item));
            }

            return $dependencies;
        }
        if (!is_string($value)) {
            return [];
        }
        $parts = explode('@', $value, 2);
        $class = $parts[0];
        $method = $parts[1] ?? null;
        if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
            return [];
        }

        return [new self($class, $method)];
    }
}
