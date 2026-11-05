<?php

use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\RouteAnnotation\Reader;

test('getData', function () {
    $reader = new Reader();
    $data = $reader->getData([fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution')]);
    ksort($data); // 排个序，防止顺序问题
    expect($data)->toMatchSnapshot();
});

test('getData 复用传入的文档配置', function () {
    $path = fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution');
    $config = new ConfigOpenapiDocDTO(scan_path: [$path], scan_exclude: ['controller']);

    expect((new Reader())->getData($config))->toBe([])
        ->and((new Reader())->getData([$path]))->not->toBe([]);
});

test('getData 不执行依赖请求上下文的 builder_modify', function () {
    $executed = false;
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/RouteAnnotation/ExampleAttribution')],
        builder_modify: function () use (&$executed): void {
            $executed = true;
        },
    );

    expect((new Reader())->getData($config))->toHaveKey('GET:/crud')
        ->and($executed)->toBeFalse();
});
