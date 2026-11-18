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
 * 将已启用 HTTP 插件的上传字段表示为二进制字符串
 */
final class UploadSchemaTranslator extends AbstractAttributeTranslator
{
    /**
     * @var list<class-string>
     */
    private array $classes = [];

    /**
     * 同一 AttributeFactory 的所有 HTTP 插件共用此 translator，不依赖外部注册表
     *
     * @param class-string $class
     */
    public function addType(string $class): void
    {
        if (!in_array($class, $this->classes, true)) {
            $this->classes[] = $class;
        }
    }

    public function supports(string $type): bool
    {
        foreach ($this->classes as $class) {
            if (is_a($type, $class, true)) {
                return true;
            }
        }

        return false;
    }

    public function translate(
        array $attributes,
        array $created,
        ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionParameter|ReflectionClassConstant $reflector,
    ): array
    {
        if (!$reflector instanceof ReflectionProperty && !$reflector instanceof ReflectionParameter) {
            return $attributes;
        }
        $property = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Property);
        if (!$property instanceof OA\Property) {
            return $attributes;
        }
        foreach (SpecHelper::propertyTypes($property, $reflector) as $type) {
            if ($this->supports($type)) {
                $schema = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema);
                if (!$schema instanceof OA\Schema) {
                    $schema = $property->schema ??= new OA\Schema();
                }
                $schema->ref = null;
                $schema->type = 'string';
                $schema->format = 'binary';
                break;
            }
        }

        return $attributes;
    }
}
