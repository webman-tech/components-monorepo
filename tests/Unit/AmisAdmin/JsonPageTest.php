<?php

use WebmanTech\AmisAdmin\Helper\ConfigHelper;
use WebmanTech\AmisAdmin\JsonPage;
use WebmanTech\CommonUtils\EnvAttr;
use WebmanTech\CommonUtils\Testing\TestConfig;

beforeEach(function () {
    ConfigHelper::$isForTest = true;
    ConfigHelper::reset();
    TestConfig::resetTestMock();
    EnvAttr::reset();
});

test('loadSchema supports placeholders and types', function () {
    TestConfig::addMock('custom.amis_admin_test', 'MyApp');
    EnvAttr::set('AMIS_ADMIN_TEST_ENV', 'testing');

    $request = request_create_one();
    request_get_raw($request)->setGet('id', '42');

    ConfigHelper::$testConfig = [
        'json_page.path' => fixture_get_path('AmisAdmin/JsonPage'),
        'json_page.vars' => function (\WebmanTech\CommonUtils\Request $request) {
            return [
                'name' => 'kriss',
                'data' => ['a' => 1],
                'num' => 123,
                'vars' => [
                    'nested' => 'ok',
                ],
                'from_request' => fn(\WebmanTech\CommonUtils\Request $request) => $request->get('id'),
            ];
        },
    ];

    $schema = JsonPage::loadSchema('demo', request_get_raw($request));

    expect($schema['title'])->toBe('MyApp')
        ->and($schema['data'])->toBe(['a' => 1])
        ->and($schema['num'])->toBe(123)
        ->and($schema['nested']['x'])->toBe('ok')
        ->and($schema['nested']['missing'])->toBe('{{missing}}')
        ->and($schema['body']['tpl'])->toBe('Hello kriss, env=testing, route=, id=42');
});

test('loadSchema rejects invalid page name', function () {
    $request = request_create_one();
    ConfigHelper::$testConfig = [
        'json_page.path' => fixture_get_path('AmisAdmin/JsonPage'),
    ];

    expect(fn() => JsonPage::loadSchema('../demo', request_get_raw($request)))
        ->toThrow(\InvalidArgumentException::class);
});

test('loadSchema throws 404 for missing file', function () {
    $request = request_create_one();
    ConfigHelper::$testConfig = [
        'json_page.path' => fixture_get_path('AmisAdmin/JsonPage'),
    ];

    expect(fn() => JsonPage::loadSchema('not-exists', request_get_raw($request)))
        ->toThrow(\RuntimeException::class);
});
