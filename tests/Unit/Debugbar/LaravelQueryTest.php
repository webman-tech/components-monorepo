<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use WebmanTech\Debugbar\Bootstrap\LaravelQuery;
use WebmanTech\Debugbar\DataCollector\LaravelQueryCollector;

function debugbar_laravel_query_listen_for_query_events(LaravelQueryCollector $collector): void
{
    $method = new ReflectionMethod(LaravelQuery::class, 'listenForQueryEvents');
    $method->setAccessible(true);
    $method->invoke(null, $collector);
}

beforeEach(function () {
    $property = new ReflectionProperty(LaravelQuery::class, 'registered');
    $property->setAccessible(true);
    $property->setValue(null, false);

    $container = Container::getInstance();
    $this->originalEvents = $container->bound('events') ? $container->make('events') : null;
});

afterEach(function () {
    $container = Container::getInstance();
    if ($this->originalEvents) {
        $container->instance('events', $this->originalEvents);
        return;
    }

    $container->forgetInstance('events');
});

it('registers query event listener only once', function () {
    $container = Container::getInstance();
    $dispatcher = $this->createMock(Dispatcher::class);
    $collector = $this->getMockBuilder(LaravelQueryCollector::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['addEventDispatcherListener'])
        ->getMock();

    $container->instance('events', $dispatcher);

    $collector->expects($this->once())
        ->method('addEventDispatcherListener')
        ->with($dispatcher);

    debugbar_laravel_query_listen_for_query_events($collector);
    debugbar_laravel_query_listen_for_query_events($collector);
});

it('ignores query event listener registration failures', function () {
    $container = Container::getInstance();
    $dispatcher = $this->createMock(Dispatcher::class);
    $collector = $this->getMockBuilder(LaravelQueryCollector::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['addEventDispatcherListener'])
        ->getMock();

    $container->instance('events', $dispatcher);

    $collector->expects($this->once())
        ->method('addEventDispatcherListener')
        ->with($dispatcher)
        ->willThrowException(new RuntimeException('listener unavailable'));

    debugbar_laravel_query_listen_for_query_events($collector);
});
