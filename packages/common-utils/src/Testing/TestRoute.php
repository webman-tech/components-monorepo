<?php

namespace WebmanTech\CommonUtils\Testing;

use WebmanTech\CommonUtils\Route\RouteObject;

final class TestRoute
{
    /**
     * @var RouteObject[]
     */
    private array $routes = [];
    /**
     * @var RouteObject[]
     */
    private array $namedRoutes = [];

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

    public function addRoute(RouteObject $item): void
    {
        $this->routes[] = $item;
        if ($item->getName() !== null) {
            $this->namedRoutes[$item->getName()] = $item;
        }
    }

    public function getRouteByName(string $name): ?RouteObject
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
