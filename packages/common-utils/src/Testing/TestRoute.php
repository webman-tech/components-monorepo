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
