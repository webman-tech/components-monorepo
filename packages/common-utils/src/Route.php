<?php

namespace WebmanTech\CommonUtils;

use Webman\Route as WebmanRoute;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use WebmanTech\CommonUtils\Route\RouteObject;

final readonly class Route
{
    public static function getCurrent(): self
    {
        $route = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_ROUTE) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_ROUTE),
            Runtime::isWebman() => null,
            default => throw new UnsupportedRuntime(),
        };
        return new self($route);
    }

    public function __construct(private mixed $route = null)
    {
    }

    public function getRaw(): mixed
    {
        return $this->route;
    }

    /**
     * 添加一个路由
     */
    public function addRoute(RouteObject $item): void
    {
        match (true) {
            Runtime::isWebman() => WebmanRoute::add($item->getMethods(), $item->getPath(), $item->getCallback())
                ->name($item->getName())
                ->middleware($item->getMiddlewares()),
            $this->isRouteHasMethod('addRoute') => $this->route->addRoute($item),
            default => throw new \InvalidArgumentException('Unsupported route type'),
        };
    }

    /**
     * 注册多个路由
     * @param RouteObject[] $routes
     */
    public function register(array $routes): void
    {
        if ($this->isRouteHasMethod('register')) {
            $this->route->register($routes);
            return;
        }
        foreach ($routes as $item) {
            $this->addRoute($item);
        }
    }

    /**
     * 根据名称获取路由
     */
    public function getRouteByName(string $name): RouteObject
    {
        $route = match (true) {
            Runtime::isWebman() => WebmanRoute::getByName($name),
            $this->isRouteHasMethod('getRouteByName') => $this->route->getRouteByName($name),
            default => throw new \InvalidArgumentException('Unsupported route type'),
        };
        return RouteObject::from($route);
    }

    /**
     * 获取所有路由
     * @return RouteObject[]
     */
    public function getRoutes(): array
    {
        $routes = match (true) {
            Runtime::isWebman() => WebmanRoute::getRoutes(),
            $this->isRouteHasMethod('getRoutes') => $this->route->getRoutes(),
            default => throw new \InvalidArgumentException('Unsupported route type'),
        };
        return array_map(fn($route) => RouteObject::from($route), $routes);
    }

    private function isRouteHasMethod(string $method): bool
    {
        if (!is_object($this->route)) {
            return false;
        }

        return method_exists($this->route, $method);
    }
}
