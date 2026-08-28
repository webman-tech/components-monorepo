<?php

declare(strict_types=1);

/*
 * e2e Pest 配置与公共 helper。
 * - Unit/ 为纯单元测试（不启动 server）；Feature/ 为集成测试（真实 webman 进程 + HTTP）
 * - Feature 闭包经 pest()->extend(Tests\TestCase::class)->in('Feature') 绑定（laravel 骨架同款机制）
 * - 仅保留 e2e 特有的断言辅助（忽略 key 顺序的比对、crontab 副作用文件统计）
 */

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

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

if (!function_exists('e2e_crontab_task_count')) {
    /**
     * 统计 cron 任务副作用文件的行数（每执行一次追加一行）；$runtimePath 为 server 的 runtime 目录
     */
    function e2e_crontab_task_count(string $runtimePath): int
    {
        $file = rtrim($runtimePath, '/') . '/crontab-task-e2e.log';

        return is_file($file) ? count(file($file, FILE_IGNORE_NEW_LINES)) : 0;
    }
}
