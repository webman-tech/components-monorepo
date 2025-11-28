<?php

namespace WebmanTech\CommonUtils\Route;

use Webman\Route\Route as WebmanRouteObject;
use WebmanTech\CommonUtils\Request;

final class RouteObject
{
    public static function from(mixed $route): self
    {
        if ($route instanceof self) {
            return $route;
        }
        if ($route instanceof WebmanRouteObject) {
            $new = new self(
                methods: $route->getMethods(),
                path: $route->getPath(),
                callback: $route->getCallback(),
                name: $route->getName() ?: null,
                middlewares: $route->getMiddleware() ?: null,
            );
            $new->setFrom($route);
            return $new;
        }
        throw new \InvalidArgumentException('Not support route type');
    }

    private array $methods;
    private mixed $fromRoute = null;

    public function __construct(
        string|array             $methods,
        private readonly string  $path,
        private readonly mixed   $callback,
        private readonly ?string $name = null,
        private readonly mixed   $middlewares = null,
    )
    {
        $this->methods = array_map('strtoupper', (array)$methods);
    }

    public function setFrom(mixed $fromRoute): self
    {
        $this->fromRoute = $fromRoute;
        return $this;
    }

    public function getFrom(): mixed
    {
        return $this->fromRoute;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCallback(): mixed
    {
        return $this->callback;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddlewares(): mixed
    {
        return $this->middlewares;
    }

    /**
     * 获取 url
     */
    public function getUrl(array $params = [], bool $appendPrefix = false): ?string
    {
        $url = match (true) {
            $this->fromRoute instanceof WebmanRouteObject => $this->fromRoute->url($params),
            $this->isRouteHasMethod('getUrl') => $this->fromRoute->getUrl($params),
            default => null,
        };
        if ($appendPrefix && ($request = Request::getCurrent())) {
            if ($prefix = $request->getPathPrefix()) {
                $url = rtrim($prefix, '/') . '/' . ltrim($url, '/');
            }
        }
        return $url;
    }

    private function isRouteHasMethod(string $method): bool
    {
        if (!is_object($this->fromRoute)) {
            return false;
        }

        return method_exists($this->fromRoute, $method);
    }
}
