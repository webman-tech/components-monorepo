<?php

declare(strict_types=1);

return [
    // 覆盖默认 handler：将 amis-admin 的 ValidationException 转为 amis 约定的 422 响应结构
    // （webman 2.x 的 exception.php 按 app 名分发，无按异常类分发机制，故只能覆盖默认 handler）
    '' => \app\exception\AppExceptionHandler::class,
];
