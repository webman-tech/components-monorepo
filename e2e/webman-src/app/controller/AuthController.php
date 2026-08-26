<?php

namespace app\controller;

use app\model\User;
use OpenApi\Attributes as OA;
use WebmanTech\Auth\Auth;

/**
 * JWT 认证链路：登录签发 token -> Authentication 中间件 + TinywanJwtMethod 校验
 */
class AuthController
{
    #[OA\Post(path: '/auth/login', summary: '登录签发 JWT')]
    public function login()
    {
        return json(User::issueToken());
    }

    #[OA\Get(path: '/auth/user', summary: '获取当前认证用户（需 Bearer token）')]
    public function user()
    {
        $user = Auth::guard()->getUser();

        return json([
            'id' => $user->getId(),
            'name' => $user->getName(),
        ]);
    }
}
