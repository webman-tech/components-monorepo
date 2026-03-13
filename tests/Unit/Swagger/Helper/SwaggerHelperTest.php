<?php

use OpenApi\Analysis;
use OpenApi\Annotations\Header as AnHeader;
use OpenApi\Annotations\Parameter as AnParameter;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\OpenApi;
use OpenApi\Context;
use Tests\Fixtures\Swagger\EnumColor;
use Tests\Fixtures\Swagger\SchemaA;
use Tests\Fixtures\Swagger\SchemaNested;
use Tests\Fixtures\Swagger\SchemaWithTrait;
use Tests\Fixtures\Swagger\TestFactory;
use WebmanTech\Swagger\Helper\SwaggerHelper;

test('getAnnotationClassName', function () {
    $analysis = TestFactory::analysisFromFiles(['SchemaA.php', 'SchemaNested.php', 'EnumColor.php', 'SchemaWithTrait.php']);

    expect(SwaggerHelper::getAnnotationClassName($analysis->getAnnotationForSource(SchemaA::class, AnSchema::class)))->toBe('\\' . SchemaA::class)
        ->and(SwaggerHelper::getAnnotationClassName($analysis->getAnnotationForSource(SchemaNested::class, AnSchema::class)))->toBe('\\' . SchemaNested::class)
        ->and(SwaggerHelper::getAnnotationClassName($analysis->getAnnotationForSource(EnumColor::class, AnSchema::class)))->toBe('\\' . EnumColor::class)
        ->and(SwaggerHelper::getAnnotationClassName($analysis->getAnnotationForSource(SchemaWithTrait::class, AnSchema::class)))->toBe('\\' . SchemaWithTrait::class);
});

test('appendComponent with parameter', function () {
    $analysis = new Analysis([], new Context());
    $analysis->openapi = new OpenApi();

    $parameter = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'test_param',
        'in' => 'query',
    ]);

    $ref = SwaggerHelper::appendComponent($analysis, $parameter);

    expect($ref)->toBe('#/components/parameters/TestParam')
        ->and($analysis->openapi->components->parameters)->toHaveKey('TestParam')
        ->and($analysis->openapi->components->parameters['TestParam'])->toBe($parameter);
});

test('appendComponent with header', function () {
    $analysis = new Analysis([], new Context());
    $analysis->openapi = new OpenApi();

    $header = new AnHeader([
        'header' => 'X-Test-Header',
        'description' => 'Test header',
    ]);

    $ref = SwaggerHelper::appendComponent($analysis, $header);

    expect($ref)->toBe('#/components/headers/X-Test-Header')
        ->and($analysis->openapi->components->headers)->toHaveKey('X-Test-Header')
        ->and($analysis->openapi->components->headers['X-Test-Header'])->toBe($header);
});

test('appendComponent with schema', function () {
    $analysis = new Analysis([], new Context());
    $analysis->openapi = new OpenApi();

    $schema = new AnSchema([
        'schema' => 'TestSchema',
        'type' => 'object',
    ]);

    $ref = SwaggerHelper::appendComponent($analysis, $schema);

    expect($ref)->toBe('#/components/schemas/TestSchema')
        ->and($analysis->openapi->components->schemas)->toHaveKey('TestSchema')
        ->and($analysis->openapi->components->schemas['TestSchema'])->toBe($schema);
});

test('appendComponent throws exception when component already exists', function () {
    $analysis = new Analysis([], new Context());
    $analysis->openapi = new OpenApi();

    $parameter1 = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'test_param',
        'in' => 'query',
    ]);
    SwaggerHelper::appendComponent($analysis, $parameter1);

    $parameter2 = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'another_param',
        'in' => 'header',
    ]);

    SwaggerHelper::appendComponent($analysis, $parameter2);
})->throws(\InvalidArgumentException::class, 'components.parameters "TestParam" already exists');

test('appendComponent with overwrite replaces existing component', function () {
    $analysis = new Analysis([], new Context());
    $analysis->openapi = new OpenApi();

    $parameter1 = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'test_param',
        'in' => 'query',
    ]);
    SwaggerHelper::appendComponent($analysis, $parameter1);

    $parameter2 = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'replaced_param',
        'in' => 'header',
    ]);
    $ref = SwaggerHelper::appendComponent($analysis, $parameter2, true);

    expect($ref)->toBe('#/components/parameters/TestParam')
        ->and($analysis->openapi->components->parameters['TestParam']->name)->toBe('replaced_param')
        ->and($analysis->openapi->components->parameters['TestParam']->in)->toBe('header');
});

test('appendComponent throws exception when openapi not defined', function () {
    $analysis = new Analysis([], new Context());

    $parameter = new AnParameter([
        'parameter' => 'TestParam',
        'name' => 'test_param',
        'in' => 'query',
    ]);

    SwaggerHelper::appendComponent($analysis, $parameter);
})->throws(\InvalidArgumentException::class, 'analysis openapi not defined');

//test('getPropertyRefByClassNameAndPropertyName', function () {
//    $analysis = analysisFromFiles(['SchemaA.php', 'SchemaB.php', 'SchemaNested.php', 'SchemaWithParent.php', 'SchemaWithTrait.php']);
//
//    $analysis->process(
//        [
//            new Processors\DocBlockDescriptions(),
//            new Processors\MergeIntoOpenApi(),
//            new Processors\MergeIntoComponents(),
//            new Processors\ExpandClasses(),
//            new Processors\ExpandInterfaces(),
//            new Processors\ExpandTraits(),
//            new Processors\ExpandEnums(),
//            new \WebmanTech\Swagger\RouteAnnotation\Processors\AugmentSchemas(),
//            new Processors\AugmentRequestBody(),
//            new Processors\AugmentProperties(),
//            new Processors\AugmentDiscriminators(),
//            new Processors\BuildPaths(),
//            new Processors\AugmentParameters(),
//            new Processors\AugmentRefs(),
//            new Processors\MergeJsonContent(),
//            new Processors\MergeXmlContent(),
//            new Processors\OperationId(),
//            new Processors\CleanUnmerged(),
//            new Processors\PathFilter(),
//            new Processors\CleanUnusedComponents(),
//            new Processors\AugmentTags(),
//
//        ]
//    );
//
//    $schemaARefName = SwaggerHelper::getSchemaRefByClassName(SchemaA::class);
//    $schemaBRefName = SwaggerHelper::getSchemaRefByClassName(SchemaB::class);
//    $schemaNestedRefName = SwaggerHelper::getSchemaRefByClassName(SchemaNested::class);
//    $schemaParentRefName = SwaggerHelper::getSchemaRefByClassName(SchemaWithParent::class);
//    $schemaTraitRefName = SwaggerHelper::getSchemaRefByClassName(SchemaWithTrait::class);
//    $schemaTraitTraitRefName = SwaggerHelper::getSchemaRefByClassName(SchemaWithTraitTrait::class);
//
//    expect(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaA::class, 'name'))
//        ->toBe($schemaARefName . '/properties/name')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaB::class, 'name'))
//        ->toBe($schemaBRefName . '/properties/name')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaNested::class, 'nestedName'))
//        ->toBe($schemaNestedRefName . '/properties/nestedName')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaNested::class, 'nestedB'))
//        ->toBe($schemaNestedRefName . '/properties/nestedB')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaWithParent::class, 'name'))
//        ->toBe($schemaBRefName . '/properties/name')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaWithParent::class, 'childName'))
//        ->toBe($schemaParentRefName . '/allOf/[1]/properties/childName')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaWithTrait::class, 'traitName'))
//        ->toBe($schemaTraitTraitRefName . '/properties/traitName')
//        ->and(SwaggerHelper::getPropertyRefByClassNameAndPropertyName($analysis, SchemaWithTrait::class, 'childName'))
//        ->toBe($schemaTraitRefName . '/allOf/[1]/properties/childName');
//});
