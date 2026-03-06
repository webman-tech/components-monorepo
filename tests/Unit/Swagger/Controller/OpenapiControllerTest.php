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
