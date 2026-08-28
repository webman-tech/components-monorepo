<?php

// webman-tech/testing 组件配置（应用侧维护；键名与 TestingConfig::fromConfig 一致）
return [
    // 自动发现的 PSR-18 HTTP 客户端构造参数（http_errors 恒为 false 不可配置）
    'httpClient' => [
        'timeout' => 5,
        'connect_timeout' => 1,
    ],
];
