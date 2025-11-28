<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Container\Container as IlluminateContainer;
use Webman\Container as WebmanContainer;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

/**
 * 容器组件
 */
final readonly class Container
{
    public static function getCurrent(): self
    {
        $container = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_CONTAINER) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_CONTAINER),
            Runtime::isWebman() => \support\Container::instance(),
            Runtime::isLaravel() => IlluminateContainer::getInstance(),
            default => throw new UnsupportedRuntime(),
        };

        return new self($container);
    }

    public static function from(object|null $container): self
    {
        return match (true) {
            $container instanceof self => $container,
            $container === null => self::getCurrent(),
            default => new self($container),
        };
    }

    public function __construct(private object $container)
    {
    }

    public function getRaw(): object
    {
        return $this->container;
    }

    /**
     * 获取组件
     * @template TClass of object
     * @param string|class-string<TClass> $name
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    public function get(string $name): mixed
    {
        $container = $this->container;
        return match (true) {
            $container instanceof WebmanContainer => $container->get($name),
            $container instanceof IlluminateContainer => $container->get($name),
            method_exists($container, 'get') => $container->get($name),
            default => throw new \InvalidArgumentException('Unsupported container type'),
        };
    }

    /**
     * 容器中是否存在
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $container = $this->container;
        return match (true) {
            $container instanceof WebmanContainer => $container->has($name),
            $container instanceof IlluminateContainer => $container->has($name),
            method_exists($container, 'has') => $container->has($name),
            default => throw new \InvalidArgumentException('Unsupported container type'),
        };
    }

    /**
     * 构建组件
     * @template TClass of object
     * @param string|class-string<TClass> $name
     * @param array $parameters
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    public function make(string $name, array $parameters = []): mixed
    {
        $container = $this->container;
        return match (true) {
            $container instanceof WebmanContainer => $container->make($name, $parameters),
            $container instanceof IlluminateContainer => $container->make($name, $parameters),
            method_exists($container, 'make') => $container->make($name, $parameters),
            default => throw new \InvalidArgumentException('Unsupported container type'),
        };
    }
}
