<?php

use WebmanTech\Swagger\Controller\OpenapiController;

test('openapiDoc', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});

test('openapiDoc use schema', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleSchema')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});

test('openapiDoc use schema request and response', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleRequestResponseAttribute')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});

test('openapiDoc mergeClassLevelInfo', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => fixture_get_path('Swagger/RouteAnnotation/ExampleMergeClassLevelInfo')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});
