<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Specification\ComponentIndex;
use OpenApi\Undefined;
use OpenApi\Utils\JsonPointer;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * Spec 引用、属性展开和媒体类型的公共转换方法
 *
 * @internal
 */
final class SpecHelper
{
    public static function first(array $items, callable $predicate): mixed
    {
        foreach ($items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * 仅替换 Undefined 哨兵值，保留 null、0、false、空字符串和空数组
     *
     * @template T
     * @template D
     * @param T $value
     * @param D $default
     * @return T|D
     */
    public static function value(mixed $value, mixed $default = null): mixed
    {
        return Undefined::isDefault($value) ? $default : $value;
    }

    /**
     * 读取并移除已消费的扩展元数据，避免内部配置进入最终文档
     */
    public static function takeX(OA\AbstractAttribute $attribute, string $key, mixed $default = null): mixed
    {
        $value = $attribute->x !== null && array_key_exists($key, $attribute->x) ? $attribute->x[$key] : $default;
        unset($attribute->x[$key]);
        if ($attribute->x === []) {
            $attribute->x = null;
        }

        return $value;
    }

    /**
     * 使用 JSON Pointer 转义组件名称，生成文档内部引用
     */
    public static function ref(OA\Schema $schema): string
    {
        if ($schema->component === null) {
            throw new \InvalidArgumentException('Schema component name is required');
        }

        return JsonPointer::ref('components', 'schemas', $schema->component);
    }

    /**
     * 沿组件别名找到实际 Schema；批量处理时复用索引，并检测循环别名
     */
    public static function resolve(Specification $spec, OA\Schema $schema, ?ComponentIndex $index = null): OA\Schema
    {
        $index ??= $spec->buildComponentIndex();
        $seen = [];
        while (($ref = $schema->getRefValue()) !== null) {
            $key = $schema->getRefKey();
            $id = $key !== null ? (string) $key : $ref;
            if (isset($seen[$id])) {
                throw new \InvalidArgumentException('Circular schema alias: ' . $id);
            }
            $seen[$id] = true;
            $target = $key !== null ? $index->findByKey($key, 'schemas') : null;
            if ($target === null && ($key === null || $key->isPlain())) {
                $target = $index->findSchema($ref);
            }
            if (!$target instanceof OA\Schema) {
                break;
            }
            $schema = $target;
        }

        return $schema;
    }

    /**
     * 将原生 Schema、组件名称或类名统一解析为 Schema
     */
    public static function schema(Specification $spec, mixed $value, ?ComponentIndex $index = null): OA\Schema
    {
        if ($value instanceof OA\Schema) {
            return $value;
        }
        if (is_string($value)) {
            $schema = ($index ?? $spec->buildComponentIndex())->findSchema($value);
            if ($schema !== null) {
                return $schema;
            }
        }
        throw new \InvalidArgumentException('Schema not found: ' . (is_string($value) ? $value : get_debug_type($value)));
    }

    /**
     * 展开 allOf 和组件引用，返回属性及其所属 Schema 的必填标记
     *
     * @return list<array{0: OA\Property, 1: bool}>
     */
    public static function properties(
        Specification $spec,
        OA\Schema $schema,
        array $seen = [],
        ?ComponentIndex $index = null,
    ): array
    {
        $index ??= $spec->buildComponentIndex();
        $schema = self::resolve($spec, $schema, $index);
        $id = spl_object_id($schema);
        if (isset($seen[$id])) {
            return [];
        }
        $seen[$id] = true;
        $properties = [];
        foreach ($schema->allOf ?? [] as $child) {
            array_push($properties, ...self::properties($spec, $child, $seen, $index));
        }
        foreach ($schema->properties ?? [] as $property) {
            $properties[] = [$property, in_array($property->property, $schema->required ?? [], true)];
        }

        return $properties;
    }

    /**
     * 优先读取显式属性类型，缺省时从反射类型提取上传文件等传输信息
     *
     * @return list<string>
     */
    public static function propertyTypes(OA\Property $property, ?\Reflector $reflector = null): array
    {
        $types = $property->x[SchemaConstants::X_PROPERTY_TYPES] ?? null;
        if (is_array($types)) {
            return array_values(array_filter($types, is_string(...)));
        }
        $reflector ??= $property->getReflector();
        $type = $reflector instanceof \ReflectionProperty || $reflector instanceof \ReflectionParameter
            ? $reflector->getType()
            : null;
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        return array_values(array_map(
            static fn (\ReflectionNamedType $type) => $type->getName(),
            array_filter($types, static fn ($type) => $type instanceof \ReflectionNamedType),
        ));
    }

    public static function isUploadedFile(string $type): bool
    {
        return is_a($type, \Webman\Http\UploadFile::class, true)
            || is_a($type, \Symfony\Component\HttpFoundation\File\UploadedFile::class, true);
    }

    /**
     * 复制属性为请求参数，避免参数转换影响原组件
     */
    public static function parameter(OA\Property $property, string $in, bool $required): OA\Parameter
    {
        $schema = $property->schema?->copy() ?? new OA\Schema();
        $schema->setReflector($schema->getReflector() ?? $property->getReflector());
        /** @var list<string> $types */
        $types = array_values(array_filter(
            is_array($schema->type) ? $schema->type : [$schema->type],
            static fn (?string $type) => $type !== null,
        ));
        if (in_array('boolean', $types, true)) {
            /*
             * Laravel boolean 验证不接受字符串 true/false，使用 0/1 供 Swagger UI 提交。
             * 联合类型（如可空布尔）一并转换，避免只有单一类型生效。
             */
            $types = array_map(
                static fn (string $type) => $type === 'boolean' ? 'integer' : $type,
                $types,
            );
            $schema->type = count($types) === 1 ? $types[0] : $types;
            $schema->enum = in_array('null', $types, true) ? [0, 1, null] : [0, 1];
        }

        return (new OA\Parameter(
            name: $property->property,
            in: $in,
            required: $in === 'path' || $required,
            description: $schema->description,
            schema: $schema,
            example: $schema->example,
            deprecated: $schema->deprecated,
        ))->setReflector($property->getReflector());
    }

    public static function header(OA\Property $property, bool $required): OA\Header
    {
        $schema = $property->schema?->copy() ?? new OA\Schema();
        $schema->setReflector($schema->getReflector() ?? $property->getReflector());

        return (new OA\Header(
            header: $property->property,
            description: $schema->description,
            required: $required,
            schema: $schema,
            example: $schema->example,
            deprecated: $schema->deprecated,
        ))->setReflector($property->getReflector());
    }

    /**
     * 二进制传输只替换类型约束，保留显式文档字段与 PHPDoc 来源
     */
    public static function binary(OA\Schema $schema, ?\Reflector $reflector = null): OA\Schema
    {
        return (new OA\Schema(
            type: 'string',
            format: 'binary',
            description: $schema->description,
            deprecated: $schema->deprecated,
            example: $schema->example,
            examples: $schema->examples,
        ))->setReflector($reflector ?? $schema->getReflector());
    }

    /**
     * 获取或创建指定状态码的响应，供多个扩展共享同一个响应对象
     */
    public static function response(OA\Operation $operation, int $status = 200): OA\Response
    {
        foreach ($operation->responses ?? [] as $response) {
            if ((string) $response->response === (string) $status) {
                return $response;
            }
        }
        $operation->responses[] = $response = new OA\Response(response: $status, description: 'OK');
        return $response;
    }

    /**
     * 获取或创建请求体、响应中的指定媒体类型
     */
    public static function media(OA\Response|OA\RequestBody $holder, string $type): OA\MediaType
    {
        foreach ($holder->content ?? [] as $media) {
            if ($media->mediaType === $type) {
                return $media;
            }
        }
        $holder->content[] = $media = new OA\MediaType(mediaType: $type);
        return $media;
    }

    /**
     * 向媒体类型追加 Schema；命名组件使用引用，内联 Schema 使用副本
     */
    public static function append(OA\MediaType $media, OA\Schema $schema, string $combine = 'allOf'): void
    {
        if (!in_array($combine, ['allOf', 'oneOf'], true)) {
            throw new \InvalidArgumentException('combine type must be allOf or oneOf');
        }
        $append = $schema->component !== null ? new OA\Schema(ref: self::ref($schema)) : $schema->copy();
        if ($media->schema === null) {
            $media->schema = $append;
        } else {
            $items = $media->schema->{$combine} ?? [$media->schema];
            $items[] = $append;
            $media->schema = new OA\Schema(...[$combine => $items]);
        }
    }
}
