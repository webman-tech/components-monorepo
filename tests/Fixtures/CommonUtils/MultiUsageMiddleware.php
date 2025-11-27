<?php

namespace Tests\Fixtures\CommonUtils;

use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;

final class MultiUsageMiddleware extends BaseMiddleware
{
    /**
     * @inheritdoc
     */
    protected function processRequest(Request $request, \Closure $handler): Response
    {
        $request->withHeaders(['X-Request-Abc' => 'abc']);

        $response = $handler($request);

        $response->withHeaders(['X-Response-Abc' => 'xyz']);

        return $response;
    }
}
