<?php

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Webman\Http\Request as WebmanRequest;
use Webman\Route\Route as WebmanRouteObject;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Route\RouteObject;
use WebmanTech\CommonUtils\Testing\TestRequest;

test('get current', function () {
    $request = Request::getCurrent();

    expect($request)->toBeInstanceOf(Request::class)
        ->and($request->getRaw())->toBeInstanceOf(TestRequest::class);
});

describe('different adapter test', function () {
    $method = 'PATCH';
    $path = '/demo/99';
    $query = ['query' => 'bar1'];
    $pathParam = '99';
    $headers = [
        'Host' => 'api.example.com',
        'Content-Type' => 'application/json',
        'X-Trace-Id' => 'trace-shared',
    ];
    $cookies = ['token' => 'abc'];
    $host = 'api.example.com';
    $postJson = ['json_key' => 'json_value'];
    $rawBody = json_encode($postJson);
    $expectedUserIp = '203.0.113.20';
    $customData = ['custom_data' => 'custom_value'];

    $httpBuffer = implode("\r\n", [
        sprintf('%s %s?query=bar1 HTTP/1.1', $method, $path),
        'Host: ' . $host,
        'Content-Type: ' . $headers['Content-Type'],
        'Content-Length: ' . strlen($rawBody),
        'X-Trace-Id: ' . $headers['X-Trace-Id'],
        'Cookie: token=' . $cookies['token'],
        'X-Forwarded-For: ' . $expectedUserIp . ', 10.0.0.1',
        '',
        $rawBody,
    ]);

    $httpFormBuffer = implode("\r\n", [
        sprintf('%s %s HTTP/1.1', $method, $path),
        'Host: ' . $host,
        'Content-Type: multipart/form-data',
        '',
        'json_key=json_value',
    ]);

    $phpServer = [
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $path,
        'REMOTE_ADDR' => '10.0.0.1',
        'QUERY_STRING' => http_build_query($query),
        'SERVER_NAME' => $host,
        'HTTP_HOST' => $host,
        'HTTP_CONTENT_TYPE' => $headers['Content-Type'],
        'HTTP_X_TRACE_ID' => $headers['X-Trace-Id'],
        'HTTP_COOKIE' => 'token=' . $cookies['token'],
        'HTTP_X_FORWARDED_FOR' => $expectedUserIp . ', 10.0.0.1',
    ];

    $cases = [
        [
            'get_request' => fn() => (new TestRequest())
                ->setData([
                    'method' => $method,
                    'path' => $path,
                    'query' => $query,
                    'pathParams' => ['id' => $pathParam],
                    'headers' => $headers,
                    'cookies' => $cookies,
                    'rawBody' => $rawBody,
                    'postJson' => $postJson,
                    'userIp' => $expectedUserIp,
                    'customData' => $customData,
                    'route' => (new RouteObject(
                        [$method],
                        $path,
                        fn() => 'ok',
                    )),
                ]),
            'get_form_request' => fn() => (new TestRequest())
                ->setData([
                    'method' => $method,
                    'postForm' => $postJson,
                ]),
            'instance_class' => TestRequest::class,
            'session_instance_class' => \WebmanTech\CommonUtils\Session::class,
            'route_instance_class' => null,
            'raw_header_reader' => fn(TestRequest $request, string $key) => $request->header($key),
        ],
        [
            'get_request' => function () use ($httpBuffer, $customData, $method, $path, $pathParam) {
                $request = new WebmanRequest($httpBuffer);
                $request->route = new WebmanRouteObject([$method], $path, fn() => 'ok');
                $request->route->setParams(['id' => $pathParam]);

                $request->{Request::CUSTOM_DATA_KEY} = $customData;

                $request->context['session'] = new \Workerman\Protocols\Http\Session('session_id'); // 模拟一下 session

                return $request;
            },
            'get_form_request' => fn() => new WebmanRequest($httpFormBuffer),
            'instance_class' => WebmanRequest::class,
            'session_instance_class' => \Workerman\Protocols\Http\Session::class,
            'route_instance_class' => WebmanRouteObject::class,
            'raw_header_reader' => fn(WebmanRequest $request, string $key) => $request->header($key),
        ],
        [
            'get_request' => function () use (
                $query,
                $customData,
                $pathParam,
                $cookies,
                $phpServer,
                $rawBody,
            ) {
                $request = new SymfonyRequest(
                    $query,
                    [],
                    [
                        Request::CUSTOM_DATA_KEY => $customData,
                        '_route_params' => [
                            'id' => $pathParam,
                        ],
                    ],
                    $cookies,
                    [],
                    $phpServer,
                    $rawBody,
                );

                $request->setSession(new Session(
                    new MockArraySessionStorage(),
                ));

                return $request;
            },
            'get_form_request' => fn() => new SymfonyRequest(
                [],
                $postJson,
                [],
                [],
                [],
                $phpServer,
            ),
            'instance_class' => SymfonyRequest::class,
            'session_instance_class' => SessionInterface::class,
            'route_instance_class' => false,
            'raw_header_reader' => fn(SymfonyRequest $request, string $key) => $request->headers->get($key),
        ],
    ];

    foreach ($cases as $case) {
        test($case['instance_class'], function () use (
            $case,
            $method,
            $path,
            $query,
            $pathParam,
            $headers,
            $cookies,
            $host,
            $rawBody,
            $postJson,
            $expectedUserIp,
            $customData,
        ) {
            $rawRequest = $case['get_request']();
            $request = Request::from($rawRequest);

            // 验证原始类型
            expect($request->getRaw())->toBeInstanceOf($case['instance_class']);

            // 验证基本数据情况
            expect($request->getMethod())->toBe($method)
                ->and($request->getPath())->toBe($path)
                ->and($request->getContentType())->toBe($headers['Content-Type'])
                ->and($request->get('query'))->toBe($query['query'])
                ->and($request->allGet())->toBe($query)
                ->and($request->path('id'))->toBe($pathParam)
                ->and($request->header('x-trace-id'))->toBe($headers['X-Trace-Id'])
                ->and($request->getHost())->toBe($headers['Host'])
                ->and($request->cookie('token'))->toBe($cookies['token'])
                ->and($request->rawBody())->toBe($rawBody)
                ->and($request->post('json_key'))->toBe('json_value')
                ->and($request->postJson('json_key'))->toBe($postJson['json_key'])
                ->and($request->allPostJson())->toBe($postJson)
                ->and($request->getUserIp())->toBe($expectedUserIp)
                ->and($request->getCustomData('custom_data'))->toBe($customData['custom_data']);

            // 修改数据与验证
            $request->withHeaders(['X-New-Header' => 'shared']);
            expect($request->header('x-new-header'))->toBe('shared')
                ->and($case['raw_header_reader']($rawRequest, 'x-new-header'))->toBe('shared');

            $request->withCustomData(['new_custom_data' => 'new_custom_value']);
            expect($request->getCustomData('new_custom_data'))->toBe('new_custom_value');

            // 验证 getSession
            $session = $request->getSession();
            expect($session->getRaw())->toBeInstanceOf($case['session_instance_class']);

            // 验证 route
            if ($case['route_instance_class'] !== false) {
                $route = $request->getRoute();
                if ($case['route_instance_class'] === null) {
                    expect($route->getFrom())->toBeNull();
                } else {
                    expect($route->getFrom())->toBeInstanceOf($case['route_instance_class']);
                }
            }

            // 验证 Form
            $rawRequest = $case['get_form_request']();
            $request = Request::from($rawRequest);
            expect($request->post('json_key'))->toBe('json_value')
                ->and($request->postForm('json_key'))->toBe($postJson['json_key'])
                ->and($request->allPostForm())->toBe($postJson);
        });
    }
});
