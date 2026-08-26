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

if (!function_exists('e2e_crontab_task_count')) {
    /**
     * 统计 cron 任务副作用文件的行数（每执行一次追加一行）
     */
    function e2e_crontab_task_count(): int
    {
        $file = e2e_server()->appDir() . '/runtime/crontab-task-e2e.log';

        return is_file($file) ? count(file($file, FILE_IGNORE_NEW_LINES)) : 0;
    }
}

if (!function_exists('e2e_crontab_task_wait_executed')) {
    /**
     * 等待 cron 任务副作用行数超过 initialCount，返回当前行数。
     *
     * workerman/crontab 的调度按整分钟对齐（new Crontab() 后等到下一个 xx:00
     * 才首次解析触发），因此轮询窗口必须覆盖跨分钟边界，最长约 60s。
     */
    function e2e_crontab_task_wait_executed(int $initialCount = 0): int
    {
        $timeout = 60 - (int)date('s') + 10;
        $deadline = microtime(true) + $timeout;
        $current = $initialCount;
        while (microtime(true) < $deadline) {
            clearstatcache();
            $current = e2e_crontab_task_count();
            if ($current > $initialCount) {
                break;
            }
            usleep(300_000);
        }

        return $current;
    }
}
