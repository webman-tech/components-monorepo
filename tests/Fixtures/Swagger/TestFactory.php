<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Generator;
use OpenApi\Pipeline;

class TestFactory
{
    /**
     * @param array<string>                                     $files
     * @param callable(\OpenApi\Generator): void|null           $configureGenerator
     */
    public static function analysisFromFiles(array $files, ?callable $configureGenerator = null): Analysis
    {
        $analysis = new Analysis([], new Context());

        $generator = (new Generator())
            ->setAnalyser(new ReflectionAnalyser([
                new AttributeAnnotationFactory()
            ]));

        if ($configureGenerator) {
            $configureGenerator($generator);
        }

        $generator->generate(array_map(fn($file) => fixture_get_path('Swagger/' . $file), $files), $analysis, false);

        return $analysis;
    }
}
