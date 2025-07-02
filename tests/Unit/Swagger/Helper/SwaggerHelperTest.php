<?php

use OpenApi\Annotations\Schema as AnSchema;
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
