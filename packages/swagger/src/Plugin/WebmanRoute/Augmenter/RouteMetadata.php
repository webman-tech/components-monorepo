<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\WebmanRoute\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 为启动期保留路由定义，或清理文档中不对外输出的路由扩展
 *
 * @implements PipeInterface<Specification>
 */
final readonly class RouteMetadata implements PipeInterface
{
    public function __construct(private bool $preserveMetadata = false)
    {
    }

    public function group(): Group
    {
        return Group::Augment;
    }

    public function __invoke(mixed $payload): Specification
    {
        if ($this->preserveMetadata) {
            foreach ($payload->operations as $operation) {
                if (($route = RouteConfigDTO::fromOperation($operation)) !== null) {
                    $operation->setMeta(RouteConfigDTO::class, $route);
                }
            }
        } else {
            $payload->getWalker()->visit(OA\AbstractAttribute::class, static function (OA\AbstractAttribute $attribute): void {
                foreach ([SchemaConstants::X_NAME, SchemaConstants::X_PATH, SchemaConstants::X_MIDDLEWARE] as $key) {
                    SpecHelper::takeX($attribute, $key);
                }
            });
        }

        return $payload;
    }
}
