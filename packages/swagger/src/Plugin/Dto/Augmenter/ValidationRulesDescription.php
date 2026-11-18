<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Dto\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 在官方 Docblocks 补全描述后，追加 DTO 验证规则
 *
 * @implements PipeInterface<Specification>
 */
final class ValidationRulesDescription implements PipeInterface
{
    public function group(): Group
    {
        return Group::Augment;
    }

    public function __invoke(mixed $payload): Specification
    {
        foreach ($payload->operations as $operation) {
            $rules = $operation->getMeta(CollectValidationRules::class . '.rules', []);
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

        return $payload;
    }
}
