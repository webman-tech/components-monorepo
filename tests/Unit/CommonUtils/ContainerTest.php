<?php

use Tests\Fixtures\CommonUtils\ContainerMakeTest;
use WebmanTech\CommonUtils\Container;
use WebmanTech\CommonUtils\Testing\TestContainer;

beforeEach(function () {
    TestContainer::addSingleton('abc', fn() => 123);
});

test('get', function () {
    expect(Container::get('abc'))->toBe(123);
});

test('has', function () {
    expect(Container::has('abc'))->toBeTrue()
        ->and(Container::has('def'))->toBeFalse();
});

test('make', function () {
    $obj = Container::make(ContainerMakeTest::class, ['abc' => 123]);
    expect($obj)->toBeInstanceOf(ContainerMakeTest::class)
        ->and($obj->abc)->toBe(123);
});
