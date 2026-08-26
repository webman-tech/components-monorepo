<?php

use app\model\User;
use WebmanTech\Auth\Authentication\FailureHandler\ResponseHandler;
use WebmanTech\Auth\Authentication\Method\HttpHeaderMethod;
use WebmanTech\Auth\Authentication\Method\TinywanJwtMethod;
use WebmanTech\Auth\Interfaces\IdentityRepositoryInterface;

return [
    // 默认 guard
    'default' => 'user',
    // 多 guard 配置
    'guards' => [
        'user' => [
            'class' => WebmanTech\Auth\Guard\Guard::class,
            'identityRepository' => fn() => new User(),
            'authenticationMethod' => fn(IdentityRepositoryInterface $identityRepository) => new TinywanJwtMethod($identityRepository),
            // 认证失败返回 401 状态码
            'authenticationFailureHandler' => fn() => new ResponseHandler(),
        ],
        // api token 的例子（Header: X-Api-Key）
        'api_token' => [
            'class' => WebmanTech\Auth\Guard\Guard::class,
            'identityRepository' => fn() => new User(),
            'authenticationMethod' => fn(IdentityRepositoryInterface $identityRepository) => new HttpHeaderMethod($identityRepository),
            'authenticationFailureHandler' => fn() => new ResponseHandler(),
        ],
    ],
];
