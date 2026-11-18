<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Dto\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 在请求展开前收集 DTO 验证规则，供描述增强使用
 *
 * @implements PipeInterface<Specification>
 */
final class CollectValidationRules implements PipeInterface
{
    public function group(): Group
    {
        return Group::Resolve;
    }

    public function __invoke(mixed $payload): Specification
    {
        $index = $payload->buildComponentIndex();
        foreach ($payload->operations as $operation) {
            $requests = $operation->x[SchemaConstants::X_SCHEMA_REQUEST] ?? [];
            $rules = [];
            foreach (is_array($requests) ? $requests : [$requests] as $request) {
                if (is_string($request)) {
                    $request = explode('@', $request, 2)[0];
                }
                $schema = SpecHelper::schema($payload, $request, $index);
                $class = $schema->getClassName();
                if ($class !== null && is_a($class, BaseRequestDTO::class, true)) {
                    $rules = array_replace($rules, $class::getValidationRules());
                }
            }
            if ($rules !== []) {
                $operation->setMeta(self::class . '.rules', $rules);
            }
        }

        return $payload;
    }
}
