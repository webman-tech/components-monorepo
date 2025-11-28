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

    public static function from(mixed $container): self
    {
        return match (true) {
            $container instanceof self => $container,
            $container === null => self::getCurrent(),
            default => new self($container),
        };
    }

    public function __construct(private mixed $container)
    {
    }

    public function getRaw(): mixed
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
        return match (true) {
            $this->container instanceof WebmanContainer => $this->container->get($name),
            $this->container instanceof IlluminateContainer => $this->container->get($name),
            method_exists($this->container, 'get') => $this->container->get($name),
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
        return match (true) {
            $this->container instanceof WebmanContainer => $this->container->has($name),
            $this->container instanceof IlluminateContainer => $this->container->has($name),
            method_exists($this->container, 'has') => $this->container->has($name),
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
        return match (true) {
            $this->container instanceof WebmanContainer => $this->container->make($name, $parameters),
            $this->container instanceof IlluminateContainer => $this->container->make($name, $parameters),
            method_exists($this->container, 'make') => $this->container->make($name, $parameters),
            default => throw new \InvalidArgumentException('Unsupported container type'),
        };
    }
}
