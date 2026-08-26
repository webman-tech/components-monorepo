<?php

declare(strict_types=1);

use Symfony\Contracts\HttpClient\ResponseInterface;
use tests\Support\WebmanServer;

if (!function_exists('e2e_server')) {
    /**
     * 共享的 webman server（自动启动）
     */
    function e2e_server(): WebmanServer
    {
        $server = WebmanServer::instance();
        $server->ensureStarted();

        return $server;
    }
}

if (!function_exists('e2e_request')) {
    /**
     * 发送 HTTP 请求并返回 Response
     */
    function e2e_request(string $method, string $path, array $options = []): ResponseInterface
    {
        return e2e_server()->request($method, $path, $options);
    }
}

if (!function_exists('e2e_array_sort_recursive')) {
    /**
     * 递归 ksort，用于忽略 key 顺序比对两个结构化数组
     */
    function e2e_array_sort_recursive(array $array): array
    {
        ksort($array);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = e2e_array_sort_recursive($value);
            }
        }

        return $array;
    }
}

if (!function_exists('e2e_json')) {
    /**
     * 发送 HTTP 请求并返回解码后的 JSON
     */
    function e2e_json(string $method, string $path, array $options = []): array
    {
        $response = e2e_request($method, $path, $options);
        expect($response->getStatusCode())->toBe(200);

        return $response->toArray();
    }
}
