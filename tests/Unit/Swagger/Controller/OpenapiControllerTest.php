<?php

use WebmanTech\Swagger\Controller\OpenapiController;

test('openapiDoc', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/yaml;charset=utf-8');
});

test('openapiDoc generate json', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution'),
        'format' => 'json',
        'cache_key' => 'json',
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/json;charset=utf-8');
});

test('openapiDoc dto example', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/ExampleDTO'),
        'format' => 'json',
        'cache_key' => 'json_dto',
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/json;charset=utf-8');
});

test('openapiDoc discriminator example', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/ExampleDiscriminator'),
        'format' => 'json',
        'cache_key' => 'json_discriminator',
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/json;charset=utf-8');
});


test('openapiDoc validation rules multi schema', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/ControllerForValidationRulesOperationDescription.php'),
        'format' => 'json',
        'cache_key' => 'json_validation_rules_multi_schema',
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/json;charset=utf-8');
});

test('openapiDoc keeps schema referenced by sub path ref when clean unused components enabled', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/ControllerForXSchemaRequestBodyProperty.php'),
        'format' => 'json',
        'cache_key' => 'json_clean_unused_sub_ref',
        'clean_unused_components_enable' => true,
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/json;charset=utf-8');
});

test('openapiDoc default uses openapi 3.1 nullable union style', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/SchemaDTO.php'),
        'format' => 'json',
        'cache_key' => 'json_schema_dto_31',
    ]);

    $json = json_decode($response->rawBody(), true, 512, JSON_THROW_ON_ERROR);
    $property = $json['components']['schemas']['SchemaDTO']['properties']['mapUseDocNullable'];

    expect($json['openapi'])->toBe('3.1.0')
        ->and($property['type'])->toBe('object')
        ->and($property)->not->toHaveKey('nullable')
        ->and($property['additionalProperties']['oneOf'])->toHaveCount(2)
        ->and($property['additionalProperties']['oneOf'][1]['type'])->toBe('null');
});

test('openapiDoc example values have correct types', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/ExampleDTO'),
        'format' => 'json',
        'cache_key' => 'json_dto_example_types',
    ]);

    $json = json_decode($response->rawBody(), true, 512, JSON_THROW_ON_ERROR);
    $props = $json['components']['schemas']['UserCreateForm']['properties'];

    // string example 保持字符串
    expect($props['name']['example'])->toBe('张三')->toBeString()
        // integer example 转为 int
        ->and($props['age']['example'])->toBe(18)->toBeInt()
        // boolean example 转为 bool
        ->and($props['is_admin']['example'])->toBe(false)->toBeBool()
        // array example 从 JSON 字符串转为数组
        ->and($props['tags']['example'])->toBe(['tag1', 'tag2'])->toBeArray();
});
