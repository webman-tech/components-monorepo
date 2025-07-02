<?php

use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\DTO\Helper\ConfigHelper;

test('with app config', function () {
    class DTOConfigWithAppConfigTestItem extends BaseConfigDTO
    {
        public function __construct(
            public string  $name = 'abc',
            public ?string $name2 = null,
        )
        {
        }
    }

    class DTOConfigWithAppConfigTest extends BaseConfigDTO
    {
        public function __construct(
            public string                          $string = 'a',
            public int                             $int = 1,
            public bool                            $bool = false,
            public array                           $array = [],
            public ?DTOConfigWithAppConfigTestItem $item = null,
        )
        {
            $this->item ??= DTOConfigWithAppConfigTestItem::fromConfig();
        }

        protected static function getAppConfig(): array
        {
            return ConfigHelper::get('app.mock_config', []);
        }
    }

    // 不指定时，取默认值
    $config = DTOConfigWithAppConfigTest::fromConfig();
    expect($config)->toBeInstanceOf(DTOConfigWithAppConfigTest::class)
        ->and($config->string)->toBe('a')
        ->and($config->int)->toBe(1)
        ->and($config->bool)->toBeFalse()
        ->and($config->array)->toBe([])
        ->and($config->item)->toBeInstanceOf(DTOConfigWithAppConfigTestItem::class)
        ->and($config->item->name)->toBe('abc');

    // 从 AppConfig 取值
    ConfigHelper::setForTest('app.mock_config', ['int' => 2, 'item' => ['name2' => 'b']]);
    $config = DTOConfigWithAppConfigTest::fromConfig();
    expect($config->int)->toBe(2)
        ->and($config->item->name2)->toBe('b');

    // 从传参取值
    $config = DTOConfigWithAppConfigTest::fromConfig(['string' => 'b', 'item' => ['name' => 'a']]);
    expect($config->string)->toBe('b')
        ->and($config->int)->toBe(2) // 不覆盖从 AppConfig 取值
        ->and($config->item->name)->toBe('a')
        ->and($config->item->name2)->toBe('b') // 不覆盖从 AppConfig 取值
    ;

    // 指定为对象时，直接返回该对象
    $newConfig = DTOConfigWithAppConfigTest::fromConfig($config);
    expect($newConfig)->toBe($config);

    // 通过 config 覆盖
    $config = DTOConfigWithAppConfigTest::fromConfig([
        'bool' => true,
    ]);
    expect($config->bool)->toBeTrue();

    ConfigHelper::setForTest();
});
