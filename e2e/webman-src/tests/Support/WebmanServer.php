<?php

/**
 * 真实 webman 进程管理
 *
 * 整个 Pest 进程共享一个 server 实例（随机空闲端口），
 * 由 shutdown function 保证停止，避免各测试文件反复启停。
 */

declare(strict_types=1);

namespace tests\Support;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class WebmanServer
{
    private static ?self $instance = null;

    private ?Process $process = null;

    private function __construct(
        private readonly string $appDir,
        private readonly int $port,
    ) {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            $appDir = dirname(__DIR__, 2);
            self::$instance = new self($appDir, self::findFreePort());
            // 无论测试进程如何退出，都尝试停掉 server 进程
            register_shutdown_function(fn() => self::$instance?->stop());
        }

        return self::$instance;
    }

    public function baseUrl(): string
    {
        return "http://127.0.0.1:{$this->port}";
    }

    public function appDir(): string
    {
        return $this->appDir;
    }

    /**
     * 幂等启动 server 并等待就绪
     */
    public function ensureStarted(float $timeout = 30): void
    {
        if ($this->process?->isRunning()) {
            return;
        }

        $this->process = new Process(
            [PHP_BINARY, 'start.php', 'start'],
            $this->appDir,
            ['E2E_WEBMAN_PORT' => (string)$this->port],
            null,
            600,
        );
        // 保证 stop 时能通知到整个进程组
        $this->process->start();

        $deadline = microtime(true) + $timeout;
        $lastError = '';
        while (microtime(true) < $deadline) {
            if (!$this->process->isRunning()) {
                $this->dumpAndThrow("webman 进程意外退出，exitCode={$this->process->getExitCode()}");
            }
            $ready = $this->probe('/health', $lastError);
            if ($ready !== null) {
                if ($ready->getStatusCode() === 200) {
                    return;
                }
                $lastError = 'health 状态码: ' . $ready->getStatusCode();
            }
            usleep(200_000);
        }

        $this->dumpAndThrow("webman 启动超时({$timeout}s)，最后错误: {$lastError}");
    }

    public function stop(): void
    {
        $process = $this->process;
        $this->process = null;
        if ($process === null || !$process->isRunning()) {
            return;
        }
        // workerman master 收到 SIGTERM 会 stop（并通知 worker）
        $process->stop(10);
    }

    /**
     * 发送 HTTP 请求（相对 baseUrl 的 path）
     */
    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        return $this->client()->request($method, $this->baseUrl() . $path, $options);
    }

    public function client(): HttpClientInterface
    {
        return HttpClient::create(['timeout' => 10]);
    }

    private function probe(string $path, string &$error): ?ResponseInterface
    {
        try {
            $response = $this->request('GET', $path);
            // 触发真实发送
            $response->getStatusCode();

            return $response;
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            return null;
        }
    }

    private static function findFreePort(): int
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0');
        if ($server === false) {
            return 8787;
        }
        $name = stream_socket_get_name($server, false);
        $port = (int)substr($name, strrpos($name, ':') + 1);
        fclose($server);

        return $port;
    }

    private function dumpAndThrow(string $message): never
    {
        $output = $this->process?->getOutput() . $this->process?->getErrorOutput();
        $this->stop();
        throw new \RuntimeException($message . "\n--- webman 进程输出 ---\n" . $output);
    }
}
