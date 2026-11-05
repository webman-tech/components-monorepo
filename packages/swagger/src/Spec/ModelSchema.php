<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Spec as OA;
use OpenApi\Type\TypeMapper;

/**
 * 从 Eloquent 模型 PHPDoc 提取可见字段
 *
 * @internal
 */
final class ModelSchema
{
    /**
     * 显式 visible/hidden 配置优先；抽象模型只解析声明，不创建实例
     *
     * @return list<OA\Property>
     */
    public static function properties(\ReflectionClass $class): array
    {
        if (!is_a($class->getName(), Model::class, true)) {
            return [];
        }
        $model = $class->isAbstract() ? null : $class->newInstance();
        $visible = $model instanceof Model ? $model->getVisible() : [];
        $hidden = $model instanceof Model ? $model->getHidden() : [];
        preg_match_all(
            '/@property[ \t]+(\S+)[ \t]+\$(\w+)[ \t]*([^\r\n]*)/',
            $class->getDocComment() ?: '',
            $matches,
            PREG_SET_ORDER,
        );
        $properties = [];
        $mapper = new TypeMapper();
        foreach ($matches as [, $type, $name, $description]) {
            if (($visible !== [] && !in_array($name, $visible, true)) || in_array($name, $hidden, true)) {
                continue;
            }
            $nullable = str_contains($type, '|null');
            $type = str_replace('|null', '', $type);
            if (is_a($type, \DateTimeInterface::class, true)) {
                $type = 'datetime';
            }
            $mapped = $mapper->map($type);
            $schema = new OA\Schema(
                type: $mapped['type'] ?? null,
                format: $mapped['format'] ?? null,
                nullable: $nullable ?: null,
                description: trim($description) ?: null,
            );
            if ($schema->type === 'mixed') {
                $schema->type = null;
            }
            if ($schema->type === 'array') {
                $schema->items = new OA\Schema();
            }
            $properties[] = new OA\Property(property: $name, schema: $schema);
        }

        return $properties;
    }
}
