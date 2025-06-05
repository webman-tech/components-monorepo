<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages/*/src',
        __DIR__ . '/scripts',
    ])
    ->withSkip([
        __DIR__ . '/packages/*/src/Install.php',
    ])
    ->withCache(__DIR__ . '/runtime/rector')
    ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
