<?php

namespace WebmanTech\CommonUtils;

use Throwable;

final class View
{
    /**
     * 渲染一个 php 模板文件
     * @param string $file
     * @param array $data
     * @return string
     */
    public static function renderPHP(string $file, array $data = []): string
    {
        extract($data);
        ob_start();
        // Try to include php file.
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean() ?: '';
    }
}
