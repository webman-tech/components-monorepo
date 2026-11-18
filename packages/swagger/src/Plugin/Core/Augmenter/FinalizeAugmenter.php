<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\Swagger\DTO\SchemaConstants as X;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 清理核心内部扩展元数据，并按配置排序文档
 *
 * @implements PipeInterface<Specification>
 */
final readonly class FinalizeAugmenter implements PipeInterface
{
    public function __construct(
        private bool $sortComponents,
        private bool $sortSchemas,
        private bool $sortPaths,
    )
    {
    }

    public function group(): Group
    {
        return Group::Augment;
    }

    public function __invoke(mixed $payload): Specification
    {
        $keys = [
            X::X_SCHEMA_REQUEST,
            X::X_SCHEMA_RESPONSE,
            X::X_SCHEMA_COMBINE_TYPE,
            X::X_PROPERTY_TYPES,
            X::X_PROPERTY_IN,
            X::X_PROPERTY_DISCRIMINATOR,
        ];
        $payload->getWalker()->visit(OA\AbstractAttribute::class, static function (OA\AbstractAttribute $attribute) use ($keys): void {
            foreach ($keys as $key) {
                SpecHelper::takeX($attribute, $key);
            }
        });
        if ($this->sortComponents) {
            if ($this->sortSchemas) {
                usort($payload->schemas, static fn (OA\Schema $a, OA\Schema $b) => strcmp($a->component ?? '', $b->component ?? ''));
            }
            if ($this->sortPaths) {
                usort($payload->operations, static fn (OA\Operation $a, OA\Operation $b) => strcmp($a->path ?? '', $b->path ?? ''));
            }
        }

        return $payload;
    }
}
