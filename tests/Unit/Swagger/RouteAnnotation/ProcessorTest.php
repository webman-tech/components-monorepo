<?php

use Illuminate\Support\Arr;
use OpenApi\Annotations as OA;
use Tests\Fixtures\Swagger\ControllerForXSchemaRequestSchemaA;
use Tests\Fixtures\Swagger\ControllerForXSchemaRequestSchemaB;
use Tests\Fixtures\Swagger\SchemaDTO;
use Tests\Fixtures\Swagger\SchemaDTOChild;
use Tests\Fixtures\Swagger\TestFactory;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\Processors\AppendResponseProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\DTOValidationRulesProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\MergeClassInfoProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\SortComponentsProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\XSchemaRequestProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\XSchemaResponseProcessor;

test('SortComponentsProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['SchemaA.php', 'SchemaB.php']);

    $analysis->process(
        new SortComponentsProcessor(),
    );

    expect(collect($analysis->openapi->components->schemas)->pluck('schema'))
        ->toMatchArray(['SchemaA', 'SchemaB']);
});

test('AppendResponseProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['ControllerNoResponse.php']);

    $analysis->process(
        new AppendResponseProcessor(),
    );

    expect($analysis->openapi->paths[0]->get->responses[200])->not->toBeEmpty();
});

test('MergeClassInfoProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['ControllerWithInfo.php']);

    $analysis->process(
        new MergeClassInfoProcessor(),
    );

    // tag
    expect($analysis->openapi->paths[0]->get->tags)->toMatchArray(['controller'])
        ->and($analysis->openapi->paths[1]->post->tags)->toMatchArray(['controller']);
});

test('XSchemaRequestProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['ControllerForXSchemaRequest.php']);

    $analysis->process(
        new XSchemaRequestProcessor(),
    );

    $fnFindPathItemByPath = function (string $path, string $method) use ($analysis): OA\Operation {
        return collect($analysis->openapi->paths)
            ->first(function (OA\PathItem $pathItem) use ($path) {
                return $pathItem->path === $path;
            })
            ->{$method};
    };

    // 在 get 下自动将 schema 转为 parameters
    $operation = $fnFindPathItemByPath('/get/schema', 'get');
    expect($operation->method)->toBe('get')
        ->and(count($operation->parameters))->toBe(1)
        ->and($operation->parameters[0]->name)->toBe('name')
        ->and($operation->x)->toBe(\OpenApi\Generator::UNDEFINED); // 用完被清理

    // 支持数组形式传递多个
    $operation = $fnFindPathItemByPath('/get/schema-multi', 'get');
    expect(count($operation->parameters))->toBe(2)
        ->and($operation->parameters[0]->name)->toBe('name')
        ->and($operation->parameters[1]->name)->toBe('age');

    // 在 post 下自动将 schema 转为 requestBody
    $operation = $fnFindPathItemByPath('/post/schema', 'post');
    expect($operation->method)->toBe('post')
        ->and($operation->requestBody->content['application/json']->schema->properties[0]->property)->toBe('name');

    // 支持 @ 自动识别 response
    $operation = $fnFindPathItemByPath('/get/schema-with-at', 'get');
    expect(count($operation->parameters))->toBe(1)
        ->and($operation->parameters[0]->name)->toBe('name')
        ->and($operation->x[SchemaConstants::X_SCHEMA_RESPONSE])->toBe(ControllerForXSchemaRequestSchemaB::class);

    // @ 自动识别 response 时，如果之前已经配置了，则不覆盖
    $operation = $fnFindPathItemByPath('/get/schema-with-at-already-has-response', 'get');
    expect($operation->x[SchemaConstants::X_SCHEMA_RESPONSE])->toBe(ControllerForXSchemaRequestSchemaA::class);

    // x-in 的支持
    $operation = $fnFindPathItemByPath('/post/schema-x-in', 'post');
    expect($operation->parameters[0]->name)->toBe('query')
        ->and($operation->parameters[0]->in)->toBe('query')
        ->and($operation->parameters[1]->name)->toBe('header')
        ->and($operation->parameters[1]->in)->toBe('header');
});

test('XSchemaResponseProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['ControllerForXSchemaResponse.php']);

    $analysis->process(
        new XSchemaResponseProcessor(),
    );

    $fnFindPathItemByPath = function (string $path, string $method) use ($analysis): OA\Operation {
        return collect($analysis->openapi->paths)
            ->first(function (OA\PathItem $pathItem) use ($path) {
                return $pathItem->path === $path;
            })
            ->{$method};
    };

    // 单 string 类，转到 200 上
    $operation = $fnFindPathItemByPath('/get/schema', 'get');
    expect($operation->responses[200]->content['application/json']->schema->properties[0]->property)->toBe('name')
        ->and($operation->x)->toBe(\OpenApi\Generator::UNDEFINED); // 用完被清理

    // 多维 index 数组，转到 200 上
    $operation = $fnFindPathItemByPath('/get/schema-multi', 'get');
    expect($operation->responses[200]->content['application/json']->schema->properties[0]->property)->toBe('name')
        ->and($operation->responses[200]->content['application/json']->schema->properties[1]->property)->toBe('age');

    // status_code 单 string
    $operation = $fnFindPathItemByPath('/get/schema-status-code', 'get');
    expect($operation->responses[200]->content['application/json']->schema->properties[0]->property)->toBe('name')
        ->and($operation->responses[201]->content['application/json']->schema->properties[0]->property)->toBe('age');

    // status_code 数组
    $operation = $fnFindPathItemByPath('/get/schema-status-code-multi', 'get');
    expect($operation->responses[200]->content['application/json']->schema->properties[0]->property)->toBe('name')
        ->and($operation->responses[200]->content['application/json']->schema->properties[1]->property)->toBe('age')
        ->and($operation->responses[201]->content['application/json']->schema->properties[0]->property)->toBe('age');

    // x-in 的支持
    $operation = $fnFindPathItemByPath('/post/schema-x-in', 'post');
    expect($operation->responses[200]->headers[0]->header)->toBe('header');
});

test('DTOValidationRulesProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['SchemaDTO.php']);

    $analysis->process(
        new DTOValidationRulesProcessor(),
    );

    $schema = $analysis->getSchemaForSource(SchemaDTO::class);
    $fnFindPropertyByName = function (string $propertyName) use ($schema): OA\Property {
        return Arr::first($schema->properties, fn(OA\Property $property) => $property->property === $propertyName);
    };

    // 校验 required
    expect($schema->required)->toBe(['name', 'age', 'arrayEmptyType', 'children', 'child', 'stringList']);

    // string
    $property = $fnFindPropertyByName('name');
    expect($property->type)->toBe('string')
        ->and($property->minLength)->toBe(5);

    // int
    $property = $fnFindPropertyByName('age');
    expect($property->type)->toBe('integer')
        ->and($property->minimum)->toBe(1)
        ->and($property->maximum)->toBe(100);

    // array 空类型
    $property = $fnFindPropertyByName('arrayEmptyType');
    expect($property->type)->toBe('array')
        ->and($property->items->type)->toBe(\OpenApi\Generator::UNDEFINED);

    // array 对象
    $property = $fnFindPropertyByName('children');
    expect($property->type)->toBe('array')
        ->and($property->items->ref)->toBe(SwaggerHelper::getSchemaRefByClassName(SchemaDTOChild::class));

    // array 列表
    $property = $fnFindPropertyByName('stringList');
    expect($property->type)->toBe('array')
        ->and($property->items->type)->toBe('string');

    // 对象
    $property = $fnFindPropertyByName('child');
    expect($property->type)->toBe('object');
});
