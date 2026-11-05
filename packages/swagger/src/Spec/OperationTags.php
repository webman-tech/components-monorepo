<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;

/**
 * 在官方分配标签后，应用类标签优先排序及 --class-skip 约定
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
            if (in_array('--class-skip', $tags, true)) {
                $method = $operation->getReflector();
                $methodTags = $method instanceof \ReflectionMethod
                    ? $byMethod[$method->class . '::' . $method->getName()] ?? []
                    : [];
                $tags = [...array_diff($tags, ['--class-skip', ...$classTags]), ...$methodTags];
            } else {
                $tags = [...$classTags, ...$tags];
            }
            $operation->tags = $tags !== [] ? array_values(array_unique($tags)) : null;
        }

        return $payload;
    }
}
