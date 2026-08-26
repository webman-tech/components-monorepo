<?php

namespace app\model;

use Tinywan\Jwt\JwtToken;
use WebmanTech\Auth\Interfaces\IdentityInterface;
use WebmanTech\Auth\Interfaces\IdentityRepositoryInterface;

/**
 * 简单的用户身份实现：id 为 e2e-user-1 的用户可被认证
 */
class User implements IdentityRepositoryInterface, IdentityInterface
{
    public const USER_ID = 'e2e-user-1';
    public const USER_NAME = 'e2e-user';

    private bool $isLogin = false;

    public function findIdentity(string $token, ?string $type = null): ?IdentityInterface
    {
        // TinywanJwtMethod 传入的 credentials 为 JwtToken::getCurrentId() 的值
        if ($token === self::USER_ID) {
            $self = new self();
            $self->isLogin = true;
            return $self;
        }
        return null;
    }

    public function getId(): ?string
    {
        return $this->isLogin ? self::USER_ID : null;
    }

    public function getName(): ?string
    {
        return $this->isLogin ? self::USER_NAME : null;
    }

    public function refreshIdentity()
    {
        return $this;
    }

    /**
     * 签发 JWT token
     */
    public static function issueToken(): array
    {
        return JwtToken::generateToken([
            'id' => self::USER_ID,
            'name' => self::USER_NAME,
        ]);
    }
}
