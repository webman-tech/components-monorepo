<?php

namespace app\controller;

use OpenApi\Attributes as OA;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Session;
use WebmanTech\Logger\Middleware\RequestTraceMiddleware;

/**
 * 回显请求信息，用于验证 common-utils 的 Request/Session/Response facade 在真实 webman 环境下的行为
 */
class EchoController
{
    #[OA\Get(path: '/echo/get', summary: '回显 GET 请求信息')]
    public function get()
    {
        $request = Request::getCurrent();

        return json([
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'query' => $request->get('foo'),
            'header' => $request->header('x-custom-header'),
            'userIp' => $request->getUserIp(),
        ]);
    }

    #[OA\Post(path: '/echo/post-json', summary: '回显 POST json 请求信息')]
    public function postJson()
    {
        $request = Request::getCurrent();

        return json([
            'method' => $request->getMethod(),
            'contentType' => $request->getContentType(),
            'name' => $request->postJson('name'),
            'age' => $request->postJson('age'),
        ]);
    }

    #[OA\Post(path: '/echo/post-form', summary: '回显 POST form 请求信息')]
    public function postForm()
    {
        $request = Request::getCurrent();

        return json([
            'method' => $request->getMethod(),
            'contentType' => $request->getContentType(),
            'name' => $request->postForm('name'),
        ]);
    }

    #[OA\Get(path: '/echo/session', summary: '写 session 并返回 traceId')]
    public function session()
    {
        $request = Request::getCurrent();
        $request->getSession()?->set('e2e_key', 'e2e_value');

        return json([
            'set' => 'e2e_key',
            'traceId' => $request->getCustomData(RequestTraceMiddleware::KEY_TRACE_ID),
        ]);
    }

    #[OA\Get(path: '/echo/session-get', summary: '读 session')]
    public function sessionGet()
    {
        return json([
            'e2e_key' => Session::getCurrent()->get('e2e_key'),
        ]);
    }

    #[OA\Get(path: '/echo/response', summary: '验证 Response facade')]
    public function response()
    {
        return Response::make()
            ->withStatus(201)
            ->withHeaders(['X-E2E-Response' => 'yes'])
            ->withBody('created by common-utils Response')
            ->getRaw();
    }
}
