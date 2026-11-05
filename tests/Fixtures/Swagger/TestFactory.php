<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger;

use OpenApi\Builder\Result;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\Spec\DocumentBuilder;

final class TestFactory
{
    public static function build(array $files = [], array $config = []): Result
    {
        return (new DocumentBuilder(new ConfigOpenapiDocDTO(...[
            'scan_path' => array_map(static fn (string $file) => __DIR__ . '/' . $file, $files),
            ...$config,
        ])))->build();
    }
}
