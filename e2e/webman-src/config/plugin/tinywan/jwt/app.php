<?php

// tinywan/jwt 的最小可用配置（HS256 + 固定 e2e 密钥，仅测试用）
return [
    'enable' => true,
    'jwt' => [
        'algorithms' => 'HS256',
        'access_secret_key' => 'e2e-webman-access-secret-key-0000000000000000000000000000',
        'access_exp' => 7200,
        'refresh_secret_key' => 'e2e-webman-refresh-secret-key-00000000000000000000000000',
        'refresh_exp' => 604800,
        'refresh_disable' => false,
        'iss' => 'e2e-webman',
        'nbf' => 0,
        'leeway' => 60,
        'is_single_device' => false,
        'cache_token_ttl' => 604800,
        'cache_token_pre' => 'JWT:TOKEN:',
        'cache_refresh_token_pre' => 'JWT:REFRESH_TOKEN:',
        'user_model' => fn($uid) => [],
    ],
];
