<?php

use WebmanTech\Logger\Middleware\HttpRequestLogMiddleware;

$container = new Webman\Container;

// e2e：请求日志全量记录（默认 logMinTimeMS=1000，快速请求不记录会导致无法断言）
$container->addDefinitions([
    HttpRequestLogMiddleware::class => fn() => new HttpRequestLogMiddleware([
        'logMinTimeMS' => 0,
    ]),
]);

return $container;
