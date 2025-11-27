<?php

use Illuminate\Container\Container as IlluminateContainer;
use Tests\Fixtures\CommonUtils\ContainerMakeTest;
use Webman\Container as WebmanContainer;
use WebmanTech\CommonUtils\Container;
use WebmanTech\CommonUtils\Testing\TestContainer;

beforeEach(function () {
    TestContainer::clear();
    TestContainer::addSingleton('abc', fn() => 123);
});

describe('different adapter test', function () {
    $cases = [
        [
            'instance_class' => TestContainer::class,
            'get_container' => function () {
                TestContainer::clear();
                TestContainer::addSingleton('abc', fn() => 123);
                return Container::getCurrent();
            },
        ],
        [
            'instance_class' => WebmanContainer::class,
            'get_container' => function () {
                $container = new WebmanContainer();
                $container->addDefinitions([
                    'abc' => fn() => 123,
                ]);
                return Container::from($container);
            },
        ],
        [
            'instance_class' => IlluminateContainer::class,
            'get_container' => function () {
                $container = new IlluminateContainer;
                $container->singleton('abc', fn() => 123);
                return Container::from($container);
            },
        ]
    ];

    foreach ($cases as $case) {
        test($case['instance_class'] . ' test', function () use ($case) {
            /** @var Container $container */
            $container = $case['get_container']();

            // 检查原实例
            expect($container->getRaw())->toBeInstanceOf($case['instance_class']);

            // 测试 get
            expect($container->get('abc'))->toBe(123);
            try {
                $container->get('not_exit');
                throw new InvalidArgumentException('cant reach here');
            } catch (Throwable $e) {
                expect($e->getMessage())->not->toBe('cant reach here');
            }

            // 测试 has
            expect($container->has('abc'))->toBeTrue()
                ->and($container->has('not_exit'))->toBeFalse();

            // 测试 make
            $obj = $container->make(ContainerMakeTest::class, ['abc' => 123]);
            expect($obj)->toBeInstanceOf(ContainerMakeTest::class)
                ->and($obj->abc)->toBe(123);
        });
    }
});
