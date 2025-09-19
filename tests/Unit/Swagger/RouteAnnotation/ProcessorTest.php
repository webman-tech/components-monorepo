<?php

use Illuminate\Support\Arr;
use OpenApi\Annotations as OA;
use Tests\Fixtures\Swagger\ControllerForXSchemaRequestSchemaA;
use Tests\Fixtures\Swagger\ControllerForXSchemaRequestSchemaB;
use Tests\Fixtures\Swagger\EnumColor;
use Tests\Fixtures\Swagger\SchemaDTO;
use Tests\Fixtures\Swagger\SchemaDTOChild;
use Tests\Fixtures\Swagger\SchemaEloquentAbsModel;
use Tests\Fixtures\Swagger\SchemaEloquentHiddenModel;
use Tests\Fixtures\Swagger\SchemaEloquentModel;
use Tests\Fixtures\Swagger\SchemaEloquentVisibleModel;
use Tests\Fixtures\Swagger\TestFactory;
use Webman\Http\UploadFile;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\RouteAnnotation\Processors\AppendResponseProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\ExpandDTOAttributionsProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\ExpandEloquentModelProcessor;
use WebmanTech\Swagger\RouteAnnotation\Processors\ExpandEnumDescriptionProcessor;
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
    expect($analysis->openapi->paths[0]->get->tags)->toBe(['controller'])
        ->and($analysis->openapi->paths[1]->post->tags)->toBe(['controller'])
        ->and($analysis->openapi->paths[2]->get->tags)->toBe(['controller', 'more-tag'])
        ->and($analysis->openapi->paths[3]->get->tags)->toBe(['only-tag']);
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
        ->and($operation->requestBody->content['application/json']->schema->ref)->toBe('#/components/schemas/ControllerForXSchemaRequestSchemaA');

    // 支持 @ 自动识别 response
    $operation = $fnFindPathItemByPath('/get/schema-with-at', 'get');
    expect(count($operation->parameters))->toBe(1)
        ->and($operation->parameters[0]->name)->toBe('name')
        ->and($operation->x[SchemaConstants::X_SCHEMA_RESPONSE])->toBe(ControllerForXSchemaRequestSchemaB::class);

    // @ 自动识别 response 时，如果之前已经配置了，则不覆盖
    $operation = $fnFindPathItemByPath('/get/schema-with-at-already-has-response', 'get');
    expect($operation->x[SchemaConstants::X_SCHEMA_RESPONSE])->toBe(ControllerForXSchemaRequestSchemaA::class);
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
    expect($operation->responses[200]->content['application/json']->schema->ref)->toBe('#/components/schemas/ControllerForXSchemaResponseSchemaA')
        ->and($operation->x)->toBe(\OpenApi\Generator::UNDEFINED); // 用完被清理

    // 多维 index 数组，转到 200 上
    $operation = $fnFindPathItemByPath('/get/schema-multi', 'get');
    expect($operation->responses[200]->content['application/json']->schema->allOf)->toHaveCount(2);
    $allOfRefs = array_map(fn(OA\Schema $schema) => $schema->ref, $operation->responses[200]->content['application/json']->schema->allOf);
    expect($allOfRefs)->toBe([
        '#/components/schemas/ControllerForXSchemaResponseSchemaA',
        '#/components/schemas/ControllerForXSchemaResponseSchemaB',
    ]);

    // status_code 单 string
    $operation = $fnFindPathItemByPath('/get/schema-status-code', 'get');
    expect($operation->responses[200]->content['application/json']->schema->ref)->toBe('#/components/schemas/ControllerForXSchemaResponseSchemaA')
        ->and($operation->responses[201]->content['application/json']->schema->ref)->toBe('#/components/schemas/ControllerForXSchemaResponseSchemaB');

    // status_code 数组
    $operation = $fnFindPathItemByPath('/get/schema-status-code-multi', 'get');
    expect($operation->responses[200]->content['application/json']->schema->allOf)->toHaveCount(2)
        ->and($operation->responses[201]->content['application/json']->schema->ref)->toBe('#/components/schemas/ControllerForXSchemaResponseSchemaB');
    $allOfRefs = array_map(fn(OA\Schema $schema) => $schema->ref, $operation->responses[200]->content['application/json']->schema->allOf);
    expect($allOfRefs)->toBe([
        '#/components/schemas/ControllerForXSchemaResponseSchemaA',
        '#/components/schemas/ControllerForXSchemaResponseSchemaB',
    ]);
});

test('ExpandDTOAttributionsProcessor', function () {
    $analysis = TestFactory::analysisFromFiles(['SchemaDTO.php']);

    $analysis->process(
        new ExpandDTOAttributionsProcessor(),
    );

    $schema = $analysis->getSchemaForSource(SchemaDTO::class);
    $fnFindPropertyByName = function (string $propertyName) use ($schema): OA\Property {
        return Arr::first($schema->properties, fn(OA\Property $property) => $property->property === $propertyName);
    };

    // 校验 required
    expect($schema->required)->toContain('name', 'age', 'arrayEmptyType', 'children', 'child', 'stringList');

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
        ->and($property->items->ref)->toBe(OA\Components::ref($analysis->getSchemaForSource(SchemaDTOChild::class)));

    // 对象
    $property = $fnFindPropertyByName('child');
    expect($property->type)->toBe('object')
        ->and($property->_context->type)->toBe('\\' . SchemaDTOChild::class);

    // datetime
    $property = $fnFindPropertyByName('date');
    expect($property->type)->toBe('string')
        ->and($property->format)->toBe('date-time');

    // file
    $property = $fnFindPropertyByName('file');
    expect($property->type)->toBe('string')
        ->and($property->format)->toBe('binary');

    // file 或 string
    $property = $fnFindPropertyByName('fileOrString');
    expect($property->type)->toBe('string')
        ->and($property->format)->toBe('binary')
        ->and($property->x[SchemaConstants::X_PROPERTY_TYPES])->toBe([
            UploadFile::class,
            'string',
        ]);

    // array 列表
    $property = $fnFindPropertyByName('stringList');
    expect($property->type)->toBe('array')
        ->and($property->items->type)->toBe('string');

    // array map
    $property = $fnFindPropertyByName('map');
    expect($property->type)->toBe('object')
        ->and($property->additionalProperties->type)->toBe('string');

    // RequestPropertyIn
    $property = $fnFindPropertyByName('hasXin');
    expect($property->x['in'])->toBe('header');

    // 联合类型
    $property = $fnFindPropertyByName('unionTypes');
    expect($property->x[SchemaConstants::X_PROPERTY_TYPES])->toBe([
        'array',
        'string',
        'int',
    ]);
});

test('ExpandEnumDescriptionProcessor', function () {
    // 未附加 ExpandEnumDescriptionProcessor 时
    $analysis = TestFactory::analysisFromFiles(['EnumColor.php']);
    $analysis->process([
        new \OpenApi\Processors\ExpandEnums(),
    ]);
    $schema = $analysis->getSchemaForSource(EnumColor::class);
    expect($schema->enum)->toBe(['red', 'green', 'blue'])
        ->and($schema->description)->toBe('颜色枚举');

    // 附加 ExpandEnumDescriptionProcessor 时
    $analysis = TestFactory::analysisFromFiles(['EnumColor.php']);
    $analysis->process([
        new \OpenApi\Processors\ExpandEnums(),
        new ExpandEnumDescriptionProcessor(),
    ]);
    $schema = $analysis->getSchemaForSource(EnumColor::class);
    expect($schema->enum)->toBe(['red', 'green', 'blue'])
        ->and($schema->description)->toBe(implode("\n", [
            '颜色枚举',
            '- red: 红色',
            '- green: 绿色',
            '- blue: 蓝色',
        ]));

    // 附加 ExpandEnumDescriptionProcessor 时，使用指定的 method 获取
    $analysis = TestFactory::analysisFromFiles(['EnumColor.php']);
    $analysis->process([
        new \OpenApi\Processors\ExpandEnums(),
        new ExpandEnumDescriptionProcessor(descriptionMethod: 'getDescription'),
    ]);
    $schema = $analysis->getSchemaForSource(EnumColor::class);
    expect($schema->enum)->toBe(['red', 'green', 'blue'])
        ->and($schema->description)->toBe(implode("\n", [
            '颜色枚举',
            '- red: 红色1',
            '- green: 绿色1',
            '- blue: 蓝色1',
        ]));

    // 附加 ExpandEnumDescriptionProcessor 时，使用指定的 method 不存在时
    $analysis = TestFactory::analysisFromFiles(['EnumColor.php']);
    $analysis->process([
        new \OpenApi\Processors\ExpandEnums(),
        new ExpandEnumDescriptionProcessor(descriptionMethod: 'getDescription2'),
    ]);
    $schema = $analysis->getSchemaForSource(EnumColor::class);
    expect($schema->enum)->toBe(['red', 'green', 'blue'])
        ->and($schema->description)->toBe(implode("\n", [
            '颜色枚举',
            '- red: R',
            '- green: G',
            '- blue: B',
        ]));
});

test('ExpandEloquentModelProcessor', function () {
    $analysis = TestFactory::analysisFromFiles([
        'SchemaEloquentModel.php',
        'SchemaEloquentHiddenModel.php',
        'SchemaEloquentVisibleModel.php',
        'SchemaEloquentAbsModel.php',
    ]);
    $analysis->process([
        new ExpandEloquentModelProcessor(),
    ]);

    $fnGetData = function (string $className) use ($analysis) {
        $schema = $analysis->getSchemaForSource($className);
        $data = [];
        foreach ($schema->properties as $property) {
            $data[] = [
                'property' => $property->property,
                'type' => $property->type,
                'description' => $property->description,
            ];
        }
        return $data;
    };
    expect($fnGetData(SchemaEloquentModel::class))->toBe([
        ['property' => 'id', 'type' => 'integer', 'description' => '(主键)'],
        ['property' => 'username', 'type' => 'string', 'description' => '用户名'],
        ['property' => 'access_token', 'type' => 'string', 'description' => 'Access Token'],
        ['property' => 'created_at', 'type' => 'string', 'description' => '创建时间'],
    ]);
    expect($fnGetData(SchemaEloquentHiddenModel::class))->toBe([
        ['property' => 'id', 'type' => 'integer', 'description' => '(主键)'],
        ['property' => 'username', 'type' => 'string', 'description' => '用户名'],
        ['property' => 'created_at', 'type' => 'string', 'description' => '创建时间'],
    ]);
    expect($fnGetData(SchemaEloquentVisibleModel::class))->toBe([
        ['property' => 'id', 'type' => 'integer', 'description' => '(主键)'],
        ['property' => 'username', 'type' => 'string', 'description' => '用户名'],
    ]);
    expect($fnGetData(SchemaEloquentAbsModel::class))->toBe([
        ['property' => 'created_at', 'type' => 'string', 'description' => '创建时间'],
    ]);
});
