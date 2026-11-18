<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec\Translator;

use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 为显式配置的类型及 trait 补充 Schema 和公开属性
 */
final class AutoSchemaTranslator extends AbstractAttributeTranslator
{
    public function __construct(private readonly array $classes)
    {
    }

    public function translate(
        array $attributes,
        array $created,
        ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionParameter|ReflectionClassConstant $reflector,
    ): array
    {
        $class = $reflector instanceof ReflectionClass ? $reflector : $reflector->getDeclaringClass();
        if ($class === null || !$this->supports($class)) {
            return $attributes;
        }
        if ($reflector instanceof ReflectionClass) {
            if (SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema) === null) {
                $attributes[] = new OA\Schema();
            }
        } elseif (($reflector instanceof ReflectionProperty && $reflector->isPublic() && !$reflector->isStatic())
            || ($reflector instanceof ReflectionParameter && $reflector->isPromoted() && $class->getProperty($reflector->getName())->isPublic())
        ) {
            if (SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Property) === null) {
                $attributes[] = (new OA\Property())->setMeta(self::class . '.generated', true);
            }
        }

        return $attributes;
    }

    private function supports(ReflectionClass $class): bool
    {
        if ($class->isTrait()) {
            return true;
        }
        foreach ($this->classes as $supported) {
            if (is_a($class->getName(), $supported, true)) {
                return true;
            }
        }

        return false;
    }
}
