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

        // 同一应用目录同时只能运行一个 workerman 实例，先清理上轮可能残留的 master
        // （否则新进程会因 "already running" 以 exitCode=0 退出）
        $this->cleanupStaleInstance();
        // worker 进程在 master 异常死亡时会成为孤儿（如 TaskProcess 每秒写副作用文件，会污染时序断言），
        // 仅在当前 server 未运行时调用（启动前/停止后），按 cwd 匹配本应用目录，不影响其它项目实例
        $this->cleanupOrphanWorkers();

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
        if ($process !== null && $process->isRunning()) {
            // workerman master 收到 SIGTERM 会 stop（并通知 worker）
            $process->stop(10);
        }
        // SIGTERM 只发给 master：worker 若未随 master 退出（或 master 被 SIGKILL），
        // 会残留为孤儿进程继续跑定时任务，这里扫尾清理
        $this->cleanupOrphanWorkers();
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
            // 与 config/process.php 的默认端口保持一致（避开 webman 官方默认 8787，防止与常规项目冲突）
            return 18787;
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

    /**
     * 清理残留的旧实例：pid 文件存在且进程存活时先 SIGTERM 优雅停止，超时 SIGKILL 兜底
     */
    private function cleanupStaleInstance(): void
    {
        $pidFile = $this->appDir . '/runtime/webman.pid';
        if (!is_file($pidFile)) {
            return;
        }

        $pid = (int)file_get_contents($pidFile);
        if ($pid > 0 && function_exists('posix_kill') && posix_kill($pid, 0)) {
            posix_kill($pid, SIGTERM);
            $deadline = microtime(true) + 5;
            while (microtime(true) < $deadline && posix_kill($pid, 0)) {
                usleep(100_000);
            }
            if (posix_kill($pid, 0)) {
                posix_kill($pid, SIGKILL);
                usleep(200_000);
            }
        }
        if (is_file($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * 清理 cwd 为本应用目录的所有 workerman 进程（master 与 worker）。
     *
     * 只在当前 server 未运行时调用（启动前/停止后）；通过 cwd 匹配避免误杀其它项目的实例。
     */
    private function cleanupOrphanWorkers(): void
    {
        if (!function_exists('posix_kill')) {
            return;
        }

        $appDir = realpath($this->appDir) ?: $this->appDir;
        $output = (string)shell_exec("ps -axo pid=,command= | grep 'WorkerMan' | grep -v grep");
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if ($line === '' || !preg_match('/^(\d+)\s+(.+)$/', $line, $m)) {
                continue;
            }
            [, $pid, $command] = $m;
            if (!str_contains($command, 'WorkerMan:')) {
                continue;
            }
            $cwd = trim((string)shell_exec(sprintf('lsof -a -p %d -d cwd -Fn 2>/dev/null | grep "^n" | cut -c2-', (int)$pid)));
            if ($cwd === '' || realpath($cwd) !== $appDir) {
                continue;
            }
            posix_kill((int)$pid, SIGKILL);
        }
        // 给 SIGKILL 一点生效时间，避免端口/文件残留
        usleep(200_000);
    }
}
