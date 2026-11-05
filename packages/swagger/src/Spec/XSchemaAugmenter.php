<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Specification\ComponentIndex;
use OpenApi\Specification\ComponentName;
use OpenApi\Utils\JsonPointer;
use OpenApi\Utils\PipeInterface;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants as X;

/**
 * 在引用解析和组件清理前，将 XSchema 扩展元数据展开为原生 Spec 对象
 *
 * @implements PipeInterface<Specification>
 */
final readonly class XSchemaAugmenter implements PipeInterface
{
    /**
     * 保存从 Schema 移出的属性，供同一组件的各个请求和响应重复使用
     */
    private const MOVED = self::class . '.properties';

    public function __construct(private ConfigOpenapiDocDTO $config)
    {
    }

    public function group(): Group
    {
        return Group::Resolve;
    }

    public function __invoke(mixed $payload): Specification
    {
        /*
         * Resolver 已收集这些类，移除临时依赖，使未被最终文档引用的组件可正常清理。
         */
        $payload->attachables = array_values(array_filter(
            $payload->attachables,
            static fn (OA\Attachable $attribute): bool => !$attribute instanceof SchemaDependency,
        ));
        $payload->getWalker()->eachSchema($this->moveProperties(...));
        /*
         * 此阶段不新增 Schema 组件，可跨接口复用索引。
         * 参数会持续追加，单独维护名称与位置的映射，避免每加一个参数就重新索引整份文档。
         */
        $index = $payload->buildComponentIndex();
        $parameters = [];
        foreach ($payload->parameters as $parameter) {
            if (($name = ComponentName::of($parameter)) !== null) {
                $parameters[$name] = (string) $parameter->in;
            }
        }
        foreach ($payload->operations as $operation) {
            $requests = SpecHelper::takeX($operation, X::X_SCHEMA_REQUEST);
            $rules = [];
            foreach ($this->list($requests) as $request) {
                if (is_string($request) && str_contains($request, '@')) {
                    [$request, $method] = explode('@', $request, 2);
                    $this->inferResponse($operation, $request, $method);
                }
                $schema = SpecHelper::schema($payload, $request, $index);
                $this->request($payload, $operation, $schema, $index, $parameters);
                $class = $schema->getClassName();
                if ($class !== null && is_a($class, BaseRequestDTO::class, true)) {
                    $rules = array_replace($rules, $class::getValidationRules());
                }
            }
            if ($this->config->append_dto_validation_rules_in_description && $rules !== []) {
                /*
                 * 等后续 Docblocks 填充接口描述后，再追加验证规则，避免覆盖原始描述。
                 */
                $operation->setMeta(self::class . '.rules', $rules);
            }
            $responses = SpecHelper::takeX($operation, X::X_SCHEMA_RESPONSE);
            if ($responses !== null) {
                if (!is_array($responses) || array_is_list($responses)) {
                    $responses = [200 => $this->list($responses)];
                }
                foreach ($responses as $status => $values) {
                    $response = SpecHelper::response($operation, (int) $status);
                    foreach ($this->list($values) as $value) {
                        $schema = is_string($value) && !class_exists($value) && !trait_exists($value) && !interface_exists($value)
                            ? new OA\Schema(description: $value)
                            : SpecHelper::schema($payload, $value, $index);
                        $this->respond($payload, $operation, $response, $schema, $index);
                    }
                }
            }
            if (empty($operation->responses)) {
                SpecHelper::response($operation);
            }
            $this->layout($payload, $operation, $index);
        }

        return $payload;
    }

    /**
     * 将单个类名、Schema 或多项配置统一为列表
     */
    private function list(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        if (is_string($value) || $value instanceof OA\Schema) {
            return [$value];
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('XSchema must be a class name, Spec Schema or array');
        }

        return $value;
    }

    /**
     * 统一请求属性位置，兼容枚举以及 get/post 的历史别名
     */
    private function in(OA\AbstractAttribute $attribute, ?string $default = null): ?string
    {
        $in = $attribute->x[X::X_PROPERTY_IN] ?? $default;
        if ($in instanceof \BackedEnum) {
            $in = $in->value;
        }
        $in = match ($in) {
            'get' => 'query',
            'post' => 'json',
            default => $in,
        };
        if ($in !== null && !in_array($in, ['query', 'path', 'header', 'cookie', 'json', 'form', 'body'], true)) {
            throw new \InvalidArgumentException('Unknown property location: ' . (string) $in);
        }

        return $in;
    }

    /**
     * 将指定位置的属性及必填标记转存为元数据，避免它们继续出现在原 Schema 中
     */
    private function moveProperties(OA\Schema $schema): void
    {
        $kept = [];
        $moved = [];
        foreach ($schema->properties ?? [] as $property) {
            $in = $this->in($property);
            if ($in === null) {
                $kept[] = $property;
                continue;
            }
            $moved[] = [$in, $property, in_array($property->property, $schema->required ?? [], true)];
            $schema->required = array_values(array_diff($schema->required ?? [], [$property->property]));
            SpecHelper::takeX($property, X::X_PROPERTY_IN);
        }
        if ($moved !== []) {
            $schema->properties = $kept ?: null;
            $schema->required = $schema->required ?: null;
            $schema->setMeta(self::MOVED, $moved);
        }
    }

    /**
     * 沿组件引用和 allOf 收集已移出的属性，循环引用只访问一次
     */
    private function moved(
        Specification $spec,
        OA\Schema $schema,
        ComponentIndex $index,
        array $seen = [],
    ): array
    {
        $schema = SpecHelper::resolve($spec, $schema, $index);
        $id = spl_object_id($schema);
        if (isset($seen[$id])) {
            return [];
        }
        $seen[$id] = true;
        $moved = $schema->getMeta(self::MOVED, []);
        if (!is_array($moved)) {
            throw new \LogicException('Invalid moved property metadata');
        }
        foreach ($schema->allOf ?? [] as $child) {
            array_push($moved, ...$this->moved($spec, $child, $index, $seen));
        }

        return $moved;
    }

    /**
     * 按属性位置展开请求参数、二进制请求体和 JSON/表单内容
     *
     * @param array<string, string> $parameters 已注册的参数组件名称及其位置
     */
    private function request(
        Specification $spec,
        OA\Operation $operation,
        OA\Schema $schema,
        ComponentIndex $index,
        array &$parameters,
    ): void
    {
        $method = $operation->method;
        $in = $this->in($schema, in_array($method, ['get', 'head', 'options'], true) ? 'query' : 'json');
        $body = false;
        foreach ($this->moved($spec, $schema, $index) as [$location, $property, $required]) {
            if (in_array($location, ['query', 'path', 'header', 'cookie'], true)) {
                $name = ($schema->component ?? $schema->getShortClassName() ?? 'Schema') . '_' . $property->property;
                if (isset($parameters[$name]) && $parameters[$name] !== $location) {
                    /*
                     * 同名属性落在不同位置时追加位置后缀，避免复用位置错误的参数组件
                     */
                    $name .= '_' . $location;
                }
                $parameter = SpecHelper::parameter($property, $location, $required);
                $parameter->component = $name;
                if (!isset($parameters[$name])) {
                    $spec->add($parameter);
                    $parameters[$name] = $location;
                }
                $operation->parameters[] = new OA\Parameter(ref: JsonPointer::ref('components', 'parameters', $name));
            } elseif ($location === 'body') {
                $operation->requestBody ??= new OA\RequestBody();
                SpecHelper::media($operation->requestBody, 'application/octet-stream')->schema ??= SpecHelper::binary(
                    $property->schema ?? new OA\Schema(),
                    $property->getReflector(),
                );
                $body = true;
            } elseif (in_array($location, ['json', 'form'], true)) {
                $operation->requestBody ??= new OA\RequestBody();
                SpecHelper::append(
                    SpecHelper::media($operation->requestBody, $location === 'form' ? 'multipart/form-data' : 'application/json'),
                    new OA\Schema(
                        type: 'object',
                        properties: [$property->copy()],
                        required: $required ? [$property->property] : null,
                    ),
                );
            }
        }
        if (in_array($in, ['query', 'path', 'header', 'cookie'], true)) {
            if ($schema->oneOf !== null) {
                throw new \InvalidArgumentException('oneOf cannot be expanded into request parameters');
            }
            foreach (SpecHelper::properties($spec, $schema, index: $index) as [$property, $required]) {
                $operation->parameters[] = SpecHelper::parameter($property, $in, $required);
            }
        } elseif ($in === 'body') {
            $operation->requestBody ??= new OA\RequestBody();
            SpecHelper::media($operation->requestBody, 'application/octet-stream')->schema = SpecHelper::binary($schema);
        } elseif (!$body && in_array($in, ['json', 'form'], true)) {
            $operation->requestBody ??= new OA\RequestBody();
            $form = $in === 'form';
            $mixed = false;
            /*
             * 纯文件字段使用 multipart；文件与普通值的联合类型同时提供 JSON 和 multipart。
             * 累积 mixed 标记，避免后续纯文件字段覆盖此前识别出的联合类型。
             */
            foreach (SpecHelper::properties($spec, $schema, index: $index) as [$property]) {
                $types = SpecHelper::propertyTypes($property);
                $uploads = array_filter($types, SpecHelper::isUploadedFile(...));
                if ($uploads !== []) {
                    $form = true;
                    $mixed = $mixed || count($uploads) < count($types);
                }
            }
            $mediaTypes = $form
                ? ($mixed ? ['application/json', 'multipart/form-data'] : ['multipart/form-data'])
                : ['application/json'];
            foreach ($mediaTypes as $mediaType) {
                SpecHelper::append(SpecHelper::media($operation->requestBody, $mediaType), $schema);
            }
        }
    }

    /**
     * 将响应 Schema 分配到响应头或响应体，并按配置组合多个响应结构
     */
    private function respond(
        Specification $spec,
        OA\Operation $operation,
        OA\Response $response,
        OA\Schema $schema,
        ComponentIndex $index,
    ): void
    {
        $body = false;
        foreach ($this->moved($spec, $schema, $index) as [$in, $property, $required]) {
            if ($in === 'header') {
                $response->headers[] = SpecHelper::header($property, $required);
            } elseif ($in === 'body') {
                SpecHelper::media($response, 'application/octet-stream')->schema ??= SpecHelper::binary(
                    $property->schema ?? new OA\Schema(),
                    $property->getReflector(),
                );
                $body = true;
            } elseif ($in === 'json') {
                SpecHelper::append(
                    SpecHelper::media($response, 'application/json'),
                    new OA\Schema(
                        type: 'object',
                        properties: [$property->copy()],
                        required: $required ? [$property->property] : null,
                    ),
                );
            }
        }
        $in = $this->in($schema, 'json');
        if ($in === 'header') {
            foreach (SpecHelper::properties($spec, $schema, index: $index) as [$property, $required]) {
                $response->headers[] = SpecHelper::header($property, $required);
            }
        } elseif ($in === 'body') {
            SpecHelper::media($response, 'application/octet-stream')->schema = SpecHelper::binary($schema);
        } elseif (!$body && (
            $schema->properties
            || $schema->ref
            || $schema->items
            || $schema->allOf
            || $schema->oneOf
            || $schema->additionalProperties !== null
            || $schema->type !== 'object'
        )) {
            $combine = $operation->x[X::X_SCHEMA_COMBINE_TYPE] ?? 'allOf';
            SpecHelper::append(SpecHelper::media($response, 'application/json'), $schema, $combine);
        }
    }

    /**
     * 从 Class@method 的返回类型推断响应；显式响应配置优先，联合类型使用 oneOf
     *
     * 未声明返回类型（或返回交集类型）时跳过推导，保留接口已有的响应定义
     */
    private function inferResponse(OA\Operation $operation, string $class, string $method): void
    {
        if (isset($operation->x[X::X_SCHEMA_RESPONSE])) {
            return;
        }
        $type = (new \ReflectionMethod($class, $method))->getReturnType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        $schemas = [];
        foreach ($types as $item) {
            if (!$item instanceof \ReflectionNamedType) {
                continue;
            }
            $name = $item->getName();
            if (!$item->isBuiltin()) {
                $schemas[] = in_array($name, ['self', 'static'], true) ? $class : $name;
            } elseif ($name !== 'void' && $name !== 'never') {
                $schemas[] = new OA\Schema(
                    type: match ($name) {
                        'int' => 'integer',
                        'float' => 'number',
                        'bool', 'true', 'false' => 'boolean',
                        'mixed' => null,
                        default => $name,
                    },
                    enum: in_array($name, ['true', 'false'], true) ? [$name === 'true'] : null,
                    items: $name === 'array' ? new OA\Schema() : null,
                );
            }
        }
        if ($schemas === []) {
            return;
        }
        $operation->x[X::X_SCHEMA_RESPONSE] = $schemas;
        if (count($schemas) > 1) {
            $operation->x[X::X_SCHEMA_COMBINE_TYPE] = 'oneOf';
        }
    }

    /**
     * 为成功的 JSON 响应套用统一包装；接口可通过显式 null 关闭包装
     */
    private function layout(Specification $spec, OA\Operation $operation, ComponentIndex $index): void
    {
        $class = SpecHelper::takeX($operation, X::X_RESPONSE_LAYOUT, $this->config->response_layout_class);
        $key = SpecHelper::takeX($operation, X::X_RESPONSE_LAYOUT_DATA_CODE, $this->config->response_layout_data_code ?? 'data');
        if ($class === null) {
            return;
        }
        $layout = SpecHelper::schema($spec, $class, $index);
        foreach ($operation->responses ?? [] as $response) {
            if ((string) $response->response !== '200') {
                continue;
            }
            foreach ($response->content ?? [] as $media) {
                if ($media->mediaType !== 'application/json' || $media->schema === null) {
                    continue;
                }
                $media->schema = new OA\Schema(allOf: [
                    new OA\Schema(ref: SpecHelper::ref($layout)),
                    new OA\Schema(type: 'object', properties: [new OA\Property(property: $key, schema: $media->schema)]),
                ]);
            }
        }
    }
}
