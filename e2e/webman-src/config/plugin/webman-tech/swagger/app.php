<?php

return [
    'enable' => true,
    'global_route' => [
        'enable' => true,
        'register_route' => false,
        'middlewares' => [],
    ],
    'host_forbidden' => [
        'enable' => false,
        'host_white_list' => [],
    ],
    'basic_auth' => [
        'enable' => false,
    ],
    'swagger_ui' => [],
    'openapi_doc' => [
        // e2e 用 json 输出，方便断言与 fixture 比对
        'format' => 'json',
        // dto-generator 依赖前端构建产物（packages/dto/web），e2e 不验证该功能
        'enable_dto_generator' => false,
    ],
];
