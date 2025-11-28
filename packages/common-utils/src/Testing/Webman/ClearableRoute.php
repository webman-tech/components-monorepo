<?php

namespace WebmanTech\CommonUtils\Testing\Webman;

use FastRoute\RouteCollector;
use Webman\Route;
use function FastRoute\simpleDispatcher;

final class ClearableRoute extends Route
{
    public static function clear(): void
    {
        self::$allRoutes = [];
        self::$nameList = [];

        /** @phpstan-ignore-next-line */
        static::$dispatcher = simpleDispatcher(function (RouteCollector $route) {
            Route::setCollector($route);
        });
    }
}
