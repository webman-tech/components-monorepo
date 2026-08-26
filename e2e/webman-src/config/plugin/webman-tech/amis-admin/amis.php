<?php

use WebmanTech\AmisAdmin\Validator\LaravelValidator;

/**
 * 覆盖插件默认配置（Install.php 落地后被本文件覆盖）：
 * - validator 换为 LaravelValidator，验证与 laravel-monorepo validator() 的集成链路
 * - page_login/app 默认值依赖 route('admin.*')，e2e 未注册这些路由，改为直接字符串
 */

$amisAssetBaseUrl = 'https://unpkg.com/amis@latest/sdk/';

return [
    'assets' => [
        'lang' => fn() => locale(),
        'css' => [
            $amisAssetBaseUrl . 'sdk.css',
            $amisAssetBaseUrl . 'helper.css',
            $amisAssetBaseUrl . 'iconfont.css',
        ],
        'js' => [
            $amisAssetBaseUrl . 'sdk.js',
        ],
        'theme' => '',
        'locale' => fn() => str_replace('_', '-', locale()),
        'debug' => false,
    ],
    'app' => [
        'amisJSON' => [
            'brandName' => 'E2E Admin',
            'api' => '/amis/menu',
        ],
        'title' => 'E2E Admin',
    ],
    'page' => [
        'amisJSON' => [],
    ],
    'page_login' => [
        'login_api' => '/amis/login-api',
        'success_redirect' => '/amis/users',
    ],
    'components' => [],
    'validator' => fn() => new LaravelValidator(validator()),
    'request_path_getter' => null,
];
