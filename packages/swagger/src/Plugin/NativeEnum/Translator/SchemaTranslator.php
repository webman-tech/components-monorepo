<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\NativeEnum\Translator;

use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 为 PHP Enum 创建 Schema，并补充 backing type
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
            return $attributes;
        }
        $class = $reflector->getName();
        if (!is_a($class, \UnitEnum::class, true)) {
            return $attributes;
        }

        $schema = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema);
        if (!$schema instanceof OA\Schema) {
            $attributes[] = $schema = new OA\Schema();
        }

        if ($schema->type === null) {
            $schema->type = (new \ReflectionEnum($class))->getBackingType()?->getName() === 'int' ? 'integer' : 'string';
        }

        return $attributes;
    }
}
