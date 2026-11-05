<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Specification\ComponentIndex;
use OpenApi\Specification\ComponentKey;
use OpenApi\Utils\PipeInterface;
use WebmanTech\DTO\Attributes\ValidationRulesDiscriminator;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 将 DTO 必填约束和 discriminator 变体补充到 Spec
 *
 * @implements PipeInterface<Specification>
 */
final class DtoAugmenter implements PipeInterface
{
    public function group(): Group
    {
        return Group::Resolve;
    }

    public function __invoke(mixed $payload): Specification
    {
        foreach ($payload->schemas as $schema) {
            foreach ($schema->properties ?? [] as $property) {
                if ($property->property === null) {
                    continue;
                }
                $reflector = $property->getReflector();
                if ($reflector instanceof \ReflectionProperty || $reflector instanceof \ReflectionParameter) {
                    $original = $reflector->getName();
                    if ($original !== $property->property && in_array($original, $schema->required ?? [], true)) {
                        /*
                         * 官方会按文档属性名推断 required；显式列表仍可能使用重命名前的 PHP 名称。
                         */
                        $schema->required = array_values(array_unique([
                            ...array_diff($schema->required, [$original]),
                            $property->property,
                        ]));
                    }
                }
                if ($property->getMeta(SchemaTranslator::class . '.required') === true) {
                    $schema->required = array_values(array_unique([...($schema->required ?? []), $property->property]));
                }
            }
        }
        $index = $payload->buildComponentIndex();
        foreach ($payload->schemas as $schema) {
            foreach ($schema->properties ?? [] as $property) {
                $discriminator = $property->getMeta(SchemaTranslator::class . '.discriminator')
                    ?? $property->x[SchemaConstants::X_PROPERTY_DISCRIMINATOR] ?? null;
                if ($discriminator !== null) {
                    $this->discriminate($payload, $schema, $property, ValidationRulesDiscriminator::fromData($discriminator), $index);
                    break;
                }
            }
        }

        return $payload;
    }

    /**
     * 按 discriminator 映射创建独立组件，并将原 Schema 改为 oneOf 入口
     */
    private function discriminate(
        Specification $spec,
        OA\Schema $schema,
        OA\Property $target,
        ValidationRulesDiscriminator $discriminator,
        ComponentIndex $index,
    ): void
    {
        $oneOf = [];
        $mapping = [];
        foreach ($discriminator->mapping as $value => $class) {
            $child = SpecHelper::schema($spec, $class, $index);
            $variant = $schema->copy();
            $variant->component = $schema->component . '_type_' . $value;
            /*
             * 通过限定键区分变体与基础组件，保留反射信息供官方 Docblocks 补充类描述。
             */
            if (($key = $schema->getComponentKey()) !== null) {
                $variant->setComponentKey(new ComponentKey($key->class, [
                    ...$key->qualifiers,
                    self::class => (string) $value,
                ]));
            }
            foreach ($variant->properties ?? [] as $property) {
                if ($property->property === $target->property) {
                    /*
                     * 保留属性显式文档字段及来源，由官方 Docblocks 在展开完成后补充 PHPDoc。
                     */
                    $property->schema ??= new OA\Schema();
                    $property->schema->ref = SpecHelper::ref($child);
                    $property->schema->oneOf = null;
                    $property->schema->anyOf = null;
                    $property->schema->allOf = null;
                } elseif ($property->property === $discriminator->property) {
                    $property->schema ??= new OA\Schema();
                    $property->schema->enum = [$value];
                }
            }
            $spec->add($variant);
            $oneOf[] = new OA\Schema(ref: SpecHelper::ref($variant));
            $mapping[$value] = SpecHelper::ref($variant);
        }
        $schema->properties = null;
        $schema->required = null;
        $schema->oneOf = $oneOf;
        $schema->discriminator = new OA\Discriminator(propertyName: $discriminator->property, mapping: $mapping);
    }
}
