<?php

namespace Tests\Fixtures;

use FastRoute\RouteCollector;
use Webman\Route;
use function FastRoute\simpleDispatcher;

class ClearableWebmanRoute extends Route
{
    public static function clean()
    {
        self::$allRoutes = [];
        self::$nameList = [];

        static::$dispatcher = simpleDispatcher(function (RouteCollector $route) {
            Route::setCollector($route);
        });
    }
}
