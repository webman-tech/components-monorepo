<?php

namespace WebmanTech\CommonUtils\Middleware;

use Closure;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;

abstract class BaseMiddleware
{
    /**
     * webman 中间件的入口方法
     */
    public function process(mixed $request, mixed $handler): mixed
    {
        $request = Request::from($request);

        $response = $this->processRequest($request, function (Request $request) use ($handler): Response {
            if (is_callable($handler)) {
                $rawResponse = $handler($request->getRaw());
            } elseif (method_exists($handler, 'handle')) {
                $rawResponse = $handler->handle($request->getRaw());
            } else {
                throw new \InvalidArgumentException('Middleware handler must be callable');
            }
            return Response::from($rawResponse);
        });

        return $response->getRaw();
    }

    /**
     * laravel 中间件的入口方法
     */
    public function handle(mixed $request, mixed $next): mixed
    {
        return $this->process($request, $next);
    }

    /**
     * 中间件逻辑
     * @param Closure(Request): Response $handler
     */
    abstract protected function processRequest(Request $request, Closure $handler): Response;
}
