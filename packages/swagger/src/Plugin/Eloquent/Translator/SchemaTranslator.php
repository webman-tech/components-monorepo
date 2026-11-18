<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Eloquent\Translator;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use WebmanTech\Swagger\Plugin\Eloquent\ModelSchema;
use WebmanTech\Swagger\Spec\SpecHelper;
use WebmanTech\Swagger\Spec\Translator\AutoSchemaTranslator;

/**
 * 将 Eloquent Model 的 PHPDoc 字段转换为 Schema 属性
 */
final class SchemaTranslator extends AbstractAttributeTranslator
{
    public function translate(
        array $attributes,
        array $created,
        ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionParameter|ReflectionClassConstant $reflector,
    ): array
    {
        if (!$reflector instanceof ReflectionClass) {
            $class = $reflector->getDeclaringClass();
            if ($class !== null && (is_a($class->getName(), Model::class, true)
                || str_starts_with($class->getName(), 'Illuminate\\Database\\Eloquent\\'))
            ) {
                /*
                 * 核心可自动描述 trait；Eloquent 的运行时配置字段由此适配器排除。
                 */
                return array_values(array_filter($attributes, static fn (object $attribute) =>
                    !$attribute instanceof OA\Property || $attribute->getMeta(AutoSchemaTranslator::class . '.generated') !== true,
                ));
            }

            return $attributes;
        }

        if (!is_a($reflector->getName(), Model::class, true)) {
            return $attributes;
        }

        $schema = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema);
        if (!$schema instanceof OA\Schema) {
            $attributes[] = $schema = new OA\Schema();
        }

        $existing = array_map(static fn (OA\Property $property) => $property->property, $schema->properties ?? []);
        foreach (ModelSchema::properties($reflector) as $property) {
            if (!in_array($property->property, $existing, true)) {
                $schema->properties[] = $property;
            }
        }

        return $attributes;
    }
}
