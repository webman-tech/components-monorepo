<?php

declare(strict_types=1);

namespace app\exception;

use support\exception\Handler;
use Throwable;
use Webman\Exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;
use WebmanTech\AmisAdmin\Exceptions\ValidationException;

/**
 * 全局异常 handler（webman 2.x 的 config/exception.php 按 app 而非异常类分发，
 * 需以覆盖默认 handler 的方式接入）：
 * 将 amis-admin 的 ValidationException 转为 amis 前端约定的 422 响应结构
 */
class AppExceptionHandler extends Handler
{
    public $dontReport = [
        BusinessException::class,
        ValidationException::class,
    ];

    public function render(Request $request, Throwable $exception): Response
    {
        if ($exception instanceof ValidationException) {
            return json([
                'status' => 1,
                'msg' => $exception->getMessage(),
                'data' => ['errors' => $exception->errors],
            ])->withStatus(422);
        }

        return parent::render($request, $exception);
    }
}
