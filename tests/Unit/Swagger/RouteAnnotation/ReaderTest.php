<?php

use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;
use WebmanTech\Swagger\RouteAnnotation\Reader;

test('getData', function () {
    $reader = new Reader();
    $data = $reader->getData(fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution'));
    $data = array_map(fn(RouteConfigDTO $item) => $item->toArray(), $data);
    $excepted = fixture_get_require('Swagger/RouteAnnotation/ExampleAttribution/controller/excepted_config.php');

    expect($data)->toBe($excepted);
});
