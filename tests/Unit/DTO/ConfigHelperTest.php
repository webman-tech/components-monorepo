<?php

use WebmanTech\CommonUtils\Testing\TestConfig;
use WebmanTech\DTO\Helper\ConfigHelper;

afterEach(function () {
    ConfigHelper::setForTest();
    TestConfig::resetTestMock();
});

test('从 app.php 的 dto 节点读取配置', function () {
    TestConfig::addMock(
        'plugin.webman-tech.dto.app.dto.data_decrypter',
        'configured-decrypter',
    );

    expect(ConfigHelper::get('dto.data_decrypter'))->toBe('configured-decrypter');
});

test('兼容从 dto.php 读取旧配置', function () {
    TestConfig::addMock(
        'plugin.webman-tech.dto.dto.data_decrypter',
        'legacy-decrypter',
    );

    expect(ConfigHelper::get('dto.data_decrypter'))->toBe('legacy-decrypter');
});
