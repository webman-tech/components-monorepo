<?php

use WebmanTech\Swagger\RouteAnnotation\Reader;

test('getData', function () {
    $reader = new Reader();
    $data = $reader->getData([fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution')]);
    ksort($data); // 排个序，防止顺序问题
    expect($data)->toMatchSnapshot();
});
