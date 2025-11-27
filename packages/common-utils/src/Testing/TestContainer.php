<?php

namespace WebmanTech\CommonUtils\Testing;

use Webman\Container;

final class TestContainer
{
    private Container $container;

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function clear(): void
    {
        self::$instance = null;
    }

    public function __construct()
    {
        $this->container = new Container();
    }

    public function get(string $name): mixed
    {
        return $this->container->get($name);
    }

    public function has(string $name): bool
    {
        return $this->container->has($name);
    }

    public function make(string $name, array $parameters = []): mixed
    {
        return $this->container->make($name, $parameters);
    }

    public static function addSingleton(string $key, \Closure $value): void
    {
        self::instance()->container->addDefinitions([$key => $value]);
    }
}
