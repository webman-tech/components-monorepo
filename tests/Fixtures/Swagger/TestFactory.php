<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Generator;

class TestFactory
{
    public static function analysisFromFiles(array $files): Analysis
    {
        $analysis = new Analysis([], new Context());

        (new Generator())
            ->setAnalyser(new ReflectionAnalyser([
                new AttributeAnnotationFactory()
            ]))
            ->generate(array_map(fn($file) => fixture_get_path('Swagger/' . $file), $files), $analysis, false);

        return $analysis;
    }
}
