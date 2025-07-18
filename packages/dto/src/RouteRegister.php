<?php

namespace WebmanTech\DTO;

use Webman\Route;

class RouteRegister
{
    /**
     * @param array<int, RouteDTO> $routes
     * @return void
     */
    public static function register(array $routes)
    {
        foreach ($routes as $route) {
            Route::add($route->method, $route->path, [self::class, 'call'])
                ->name($route->name)
                ->middleware($route->middlewares ?? []);
        }
    }

    public function call(string $controller, string $action)
    {
        $form = $controller::fromRequest($request, $method);
        $result = $form->{$action}();
        return $result->toResponse();
    }
}
