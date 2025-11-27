<?php

namespace WebmanTech\CommonUtils\Route;

use Webman\Route\Route as WebmanRouteObject;

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
                name: $route->getName(),
                middlewares: $route->getMiddleware(),
            );
            $new->setFrom($route);
            return $new;
        }
        throw new \InvalidArgumentException('Not support route type');
    }

    private array $methods;
    private mixed $fromRoute = null;

    public function __construct(
        string|array              $methods,
        private readonly string   $path,
        private readonly \Closure $callback,
        private readonly ?string  $name = null,
        private readonly mixed    $middlewares = null,
    )
    {
        $this->methods = array_map('strtoupper', (array)$methods);
    }

    public function setFrom(mixed $fromRoute): void
    {
        $this->fromRoute = $fromRoute;
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

    public function getCallback(): \Closure
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
}
