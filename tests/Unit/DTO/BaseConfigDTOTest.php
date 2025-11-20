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
            /**
             * @var DTOConfigWithAppConfigTestItem[]
             */
            public array                           $items = [],
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
        ->and($config->item->name)->toBe('abc')
        ->and($config->items)->toBe([]);

    // 从 AppConfig 取值
    ConfigHelper::setForTest('app.mock_config', ['int' => 2, 'item' => ['name2' => 'b'], 'items' => [['name2' => 'c']]]);
    $config = DTOConfigWithAppConfigTest::fromConfig();
    expect($config->int)->toBe(2)
        ->and($config->item->name2)->toBe('b')
        ->and($config->items[0]->name2)->toBe('c');

    // 从传参取值
    $config = DTOConfigWithAppConfigTest::fromConfig(['string' => 'b', 'item' => ['name' => 'a'], 'items' => [['name' => 'x']]]);
    expect($config->string)->toBe('b')
        ->and($config->int)->toBe(2) // 不覆盖从 AppConfig 取值
        ->and($config->item->name)->toBe('a')
        ->and($config->item->name2)->toBe('b') // 不覆盖从 AppConfig 取值
        ->and($config->items[0]->name2)->toBe('c') // 注意此处 list 类型的 array 合并是产出多个数组，而不是合并
        ->and($config->items[1]->name)->toBe('x'); // 同上

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

test('config construct not public', function () {
    class DTOConfigConstructNotPublicTest extends BaseConfigDTO
    {
        public int $age;
        public string $another_name_alias;

        public function __construct(
            public string $name = 'abc',
            int|null      $age = null,
            string|null   $another_name = null,
        )
        {
            $this->age = $age ?? 18;
            $this->another_name_alias = $another_name ?? 'another_name';
        }
    }

    // 默认值情况
    $config = DTOConfigConstructNotPublicTest::fromConfig();
    expect($config->name)->toBe('abc')
        ->and($config->age)->toBe(18)
        ->and($config->another_name_alias)->toBe('another_name');

    // 配置特定值
    $config = DTOConfigConstructNotPublicTest::fromConfig([
        'name' => 'b',
        'age' => 20,
        'another_name' => 'c',
    ]);
    expect($config->name)->toBe('b')
        ->and($config->age)->toBe(20)
        ->and($config->another_name_alias)->toBe('c');
});
