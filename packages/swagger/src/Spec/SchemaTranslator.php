<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Assembler\AbstractAttributeTranslator;
use OpenApi\Spec as OA;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 在组装和引用解析前，将 DTO 元数据转换为原生 Spec 属性
 */
final class SchemaTranslator extends AbstractAttributeTranslator
{
    public function __construct(private readonly ConfigOpenapiDocDTO $config)
    {
    }

    public function translate(
        array $attributes,
        array $created,
        ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionParameter|ReflectionClassConstant $reflector,
    ): array
    {
        if ($reflector instanceof ReflectionClass) {
            $schema = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema);
            if ($schema === null && $this->supports($reflector)) {
                $attributes[] = $schema = new OA\Schema();
            }
            if ($schema instanceof OA\Schema) {
                if ($this->config->expand_eloquent_model_enable) {
                    $existing = array_map(static fn (OA\Property $property) => $property->property, $schema->properties ?? []);
                    foreach (ModelSchema::properties($reflector) as $property) {
                        if (!in_array($property->property, $existing, true)) {
                            $schema->properties[] = $property;
                        }
                    }
                }
                if (is_a($reflector->getName(), \UnitEnum::class, true) && $schema->type === null) {
                    $backingType = (new \ReflectionEnum($reflector->getName()))->getBackingType()?->getName();
                    $schema->type = $backingType === 'int' ? 'integer' : 'string';
                }
            }

            return $attributes;
        }

        if (!$reflector instanceof ReflectionProperty
            && !($reflector instanceof ReflectionParameter && $reflector->isPromoted())
        ) {
            return $attributes;
        }
        $class = $reflector->getDeclaringClass();
        if ($class === null) {
            return $attributes;
        }
        if ($reflector instanceof ReflectionProperty && (!$reflector->isPublic() || $reflector->isStatic())) {
            return $attributes;
        }
        if ($reflector instanceof ReflectionParameter && !$class->getProperty($reflector->getName())->isPublic()) {
            return $attributes;
        }
        $property = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Property);
        /*
         * Eloquent 字段由 ModelSchema 从 PHPDoc 提取；公开的运行时配置属性仅在显式标注时导出。
         */
        if ($property === null
            && $this->supports($class)
            && !is_a($class->getName(), \Illuminate\Database\Eloquent\Model::class, true)
            && !str_starts_with($class->getName(), 'Illuminate\\Database\\Eloquent\\')
        ) {
            $attributes[] = $property = new OA\Property();
        }
        if (!$property instanceof OA\Property || !is_a($class->getName(), BaseDTO::class, true)) {
            return $attributes;
        }
        $property->property ??= $reflector->getName();
        $factory = ReflectionReaderFactory::fromClass($class->getName());
        $schema = SpecHelper::first($attributes, static fn (object $item) => $item instanceof OA\Schema);
        $schema ??= $property->schema;
        if (!$schema instanceof OA\Schema) {
            $property->schema = $schema = new OA\Schema();
        }
        if ($rules = $factory->getAttributionValidationRules($reflector->getName())) {
            $this->applyRules($schema, $rules);
            $property->setMeta(self::class . '.required', $rules->required);
            if ($rules->discriminator !== null) {
                $property->setMeta(self::class . '.discriminator', $rules->discriminator);
                $schema->oneOf = array_map(
                    static fn (string $class) => new OA\Schema(ref: $class),
                    array_values($rules->discriminator->mapping),
                );
            }
        }
        if ($in = $factory->getAttributionRequestPropertyIn($reflector->getName())) {
            $property->property = $in->name ?: $property->property;
            $property->x[SchemaConstants::X_PROPERTY_IN] = $in->getInEnum()->value;
        }
        /*
         * 上传文件应表示为二进制字符串，避免将传输对象展开成可复用组件。
         */
        foreach (SpecHelper::propertyTypes($property, $reflector) as $type) {
            if (SpecHelper::isUploadedFile($type)) {
                $schema->ref = null;
                $schema->type = 'string';
                $schema->format = 'binary';
            }
        }

        return $attributes;
    }

    private function supports(ReflectionClass $class): bool
    {
        if ($class->isEnum() || $class->isTrait()) {
            return true;
        }
        foreach ($this->config->auto_load_schema_classes as $supported) {
            if (is_a($class->getName(), $supported, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 将验证规则补充为 Schema 约束，保留用户显式声明的类型和边界值
     */
    private function applyRules(OA\Schema $schema, ValidationRules $rules): void
    {
        $schema->type ??= match (true) {
            $rules->integer === true => 'integer',
            $rules->numeric === true => 'number',
            $rules->boolean === true => 'boolean',
            $rules->string === true => 'string',
            $rules->object === true => 'object',
            $rules->array === true || $rules->arrayItem !== null => 'array',
            default => null,
        };
        $schema->minimum ??= $rules->min;
        $schema->maximum ??= $rules->max;
        $schema->minLength ??= $rules->minLength;
        $schema->maxLength ??= $rules->maxLength;
        $schema->nullable ??= $rules->nullable;
        if (is_string($rules->object) && is_a($rules->object, BaseDTO::class, true)) {
            $schema->ref ??= $rules->object;
        }
        if ($rules->arrayItem !== null) {
            $item = new OA\Schema();
            if ($rules->arrayItem instanceof ValidationRules) {
                $this->applyRules($item, $rules->arrayItem);
            } elseif (is_string($rules->arrayItem)) {
                $item->ref = $rules->arrayItem;
            }
            if ($rules->object !== null) {
                /*
                 * 带 arrayItem 的 object 表示键值映射，值类型由 additionalProperties 描述。
                 */
                $schema->type = 'object';
                $schema->items = null;
                if ($rules->objectValueNullable) {
                    $item = new OA\Schema(oneOf: [$item, new OA\Schema(type: 'null')]);
                }
                $schema->additionalProperties = $item;
            } else {
                $schema->items ??= $item;
            }
        }
    }
}
