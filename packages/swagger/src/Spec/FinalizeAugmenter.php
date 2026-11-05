<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Spec as OA;
use OpenApi\Specification;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants as X;

/**
 * 编译前补充业务描述、清理内部扩展元数据并排序
 */
final readonly class FinalizeAugmenter
{
    public function __construct(
        private ConfigOpenapiDocDTO $config,
        private bool $routeMetadata = false,
    )
    {
    }

    public function __invoke(Specification $spec): Specification
    {
        foreach ($spec->operations as $operation) {
            $rules = $operation->getMeta(XSchemaAugmenter::class . '.rules', []);
            if (is_array($rules) && $rules !== []) {
                $lines = ['```php', '// Validation Rules', '['];
                foreach ($rules as $key => $values) {
                    if (!is_array($values)) {
                        continue;
                    }
                    $rendered = array_map(
                        static fn ($value) => is_string($value) ? "'{$value}'" : '__' . gettype($value),
                        $values,
                    );
                    $lines[] = "    '{$key}' => [" . implode(', ', $rendered) . '],';
                }
                $operation->description = (
                    SpecHelper::value($operation->description, '') !== ''
                        ? SpecHelper::value($operation->description) . "\n"
                        : ''
                ) . implode("\n", [...$lines, ']', '```']);
            }
        }

        if ($this->config->schema_enum_description_enable) {
            foreach ($spec->schemas as $schema) {
                $class = $schema->getClassName();
                if ($class === null || !is_a($class, \BackedEnum::class, true) || $schema->enum === null) {
                    continue;
                }
                $method = $this->config->schema_enum_description_method ?? 'description';
                $lines = [];
                foreach ($schema->enum as $value) {
                    if (!is_int($value) && !is_string($value)) {
                        continue;
                    }
                    $case = $class::tryFrom($value);
                    if ($case === null) {
                        continue;
                    }
                    $description = method_exists($case, $method) ? $case->{$method}() : $case->name;
                    if (strtolower(str_replace(['_', '-'], '', (string) $value))
                        !== strtolower(str_replace(['_', '-'], '', $description))
                    ) {
                        $lines[] = "- {$value}: {$description}";
                    }
                }
                if ($lines !== []) {
                    $description = SpecHelper::value($schema->description, '');
                    $schema->description = ($description !== '' ? $description . "\n" : '') . implode("\n", $lines);
                }
            }
        }

        $keys = [
            X::X_SCHEMA_REQUEST,
            X::X_SCHEMA_RESPONSE,
            X::X_SCHEMA_COMBINE_TYPE,
            X::X_PROPERTY_TYPES,
            X::X_PROPERTY_IN,
            X::X_PROPERTY_DISCRIMINATOR,
        ];
        if (!$this->routeMetadata) {
            /*
             * 路由解析依赖这几个扩展值；只有文档输出时才清除。
             */
            array_push($keys, X::X_NAME, X::X_PATH, X::X_MIDDLEWARE);
        }
        $spec->getWalker()->visit(OA\AbstractAttribute::class, static function (OA\AbstractAttribute $attribute) use ($keys): void {
            foreach ($keys as $key) {
                SpecHelper::takeX($attribute, $key);
            }
        });
        if ($this->config->sort_components_enable) {
            if ($this->config->sort_components_sort_schemas) {
                usort($spec->schemas, static fn (OA\Schema $a, OA\Schema $b) => strcmp($a->component ?? '', $b->component ?? ''));
            }
            if ($this->config->sort_components_sort_paths) {
                usort($spec->operations, static fn (OA\Operation $a, OA\Operation $b) => strcmp($a->path ?? '', $b->path ?? ''));
            }
        }

        return $spec;
    }
}
