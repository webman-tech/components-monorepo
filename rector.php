<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages/*/src',
        __DIR__ . '/scripts',
    ])
    ->withSkip([
        __DIR__ . '/packages/*/src/Install.php',
    ])
    ->withCache(__DIR__ . '/runtime/rector')
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php82: true)
    ->withRules([
        ExplicitNullableParamTypeRector::class,
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
