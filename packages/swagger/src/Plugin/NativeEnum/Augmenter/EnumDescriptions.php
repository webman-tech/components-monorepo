<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\NativeEnum\Augmenter;

use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Utils\PipeInterface;
use WebmanTech\Swagger\Spec\SpecHelper;

/**
 * 将 PHP Enum case 的描述补充到 Schema
 *
 * @implements PipeInterface<Specification>
 */
final readonly class EnumDescriptions implements PipeInterface
{
    public function __construct(private string $descriptionMethod)
    {
    }

    public function group(): Group
    {
        return Group::Augment;
    }

    public function __invoke(mixed $payload): Specification
    {
        foreach ($payload->schemas as $schema) {
            $class = $schema->getClassName();
            if ($class === null || !is_a($class, \BackedEnum::class, true) || $schema->enum === null) {
                continue;
            }
            $lines = [];
            foreach ($schema->enum as $value) {
                if (!is_int($value) && !is_string($value)) {
                    continue;
                }
                $case = $class::tryFrom($value);
                if ($case === null) {
                    continue;
                }
                $description = method_exists($case, $this->descriptionMethod) ? $case->{$this->descriptionMethod}() : $case->name;
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

        return $payload;
    }
}
