<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 在官方分配标签后应用类级别和方法级别 Tag，并处理显式的跳过配置
 *
 * @implements PipeInterface<Specification>
 */
final class OperationTags implements PipeInterface
{
    public function group(): Group
    {
        return Group::Resolve;
    }

    public function __invoke(mixed $payload): Specification
    {
        $byClass = [];
        $byMethod = [];
        foreach ($payload->tags as $tag) {
            if ($tag->name === null) {
                continue;
            }
            $reflector = $tag->getReflector();
            if ($reflector instanceof \ReflectionClass) {
                $byClass[$reflector->getName()][] = $tag->name;
            } elseif ($reflector instanceof \ReflectionMethod) {
                $byMethod[$reflector->class . '::' . $reflector->getName()][] = $tag->name;
            }
        }

        foreach ($payload->operations as $operation) {
            $tags = $operation->tags ?? [];
            $classTags = $byClass[$operation->getClassName() ?? ''] ?? [];
            $skipClassTags = (bool) SpecHelper::takeX($operation, SchemaConstants::X_SKIP_CLASS_TAG, false);
            if ($skipClassTags) {
                $method = $operation->getReflector();
                $methodTags = $method instanceof \ReflectionMethod
                    ? $byMethod[$method->class . '::' . $method->getName()] ?? []
                    : [];
                $tags = [...array_diff($tags, $classTags), ...$methodTags];
            } else {
                $tags = [...$classTags, ...$tags];
            }
            $operation->tags = $tags !== [] ? array_values(array_unique($tags)) : null;
        }

        return $payload;
    }
}
