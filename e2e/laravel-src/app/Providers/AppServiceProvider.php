<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use WebmanTech\Logger\Logger;
use WebmanTech\Logger\Middleware\HttpRequestLogMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // e2e：请求日志全量记录（默认 logMinTimeMS=1000，快速请求不记录会导致无法断言）
        // laravel 容器解析中间件时经由此绑定注入构造参数
        $this->app->bind(HttpRequestLogMiddleware::class, fn() => new HttpRequestLogMiddleware([
            'logMinTimeMS' => 0,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // e2e：将 logger 包生成的 channel 配置合并到 laravel logging
        // 包生成的 handlers 为声明式配置（webman config 格式：class/constructor/formatter），
        // 需实例化为 Monolog Handler 后用 custom driver + via 闭包构建 Monolog 实例
        $channels = [];
        foreach (Logger::getLogChannelConfigs() as $name => $config) {
            $channels[$name] = [
                'driver' => 'custom',
                'via' => function (array $channelConfig) use ($name) {
                    $handlers = [];
                    foreach ($channelConfig['handlers'] as $handlerConfig) {
                        $handlers[] = $this->buildMonologHandler($handlerConfig);
                    }

                    return new \Monolog\Logger($name, $handlers, $channelConfig['processors']);
                },
            ] + $config;
        }
        config(['logging.channels' => array_merge(config('logging.channels', []), $channels)]);
    }

    /**
     * 将声明式 handler 配置实例化为 Monolog Handler
     */
    private function buildMonologHandler(array $config): \Monolog\Handler\HandlerInterface
    {
        /** @var \Monolog\Handler\HandlerInterface $handler */
        $handler = new $config['class'](...$config['constructor']);

        if ($formatter = $config['formatter'] ?? null) {
            $handler->setFormatter(new $formatter['class'](...($formatter['constructor'] ?? [])));
        }

        return $handler;
    }
}
