<?php

namespace Tests\Fixtures\Auth\Models;

use support\Model;
use WebmanTech\Auth\Interfaces\IdentityInterface;
use WebmanTech\Auth\Interfaces\IdentityRepositoryInterface;

class User extends Model implements IdentityRepositoryInterface, IdentityInterface
{
    public const MOCK_TOKEN = 'mock_token';
    public const MOCK_TOKEN_BASIC = 'user:mock_token';
    public const MOCK_ID = 'mock_id';

    private bool $isLogin = false;
    private bool $isRefreshed = false;

    public function findIdentity(string $token, ?string $type = null): ?IdentityInterface
    {
        if ($token === self::MOCK_TOKEN || $token === self::MOCK_TOKEN_BASIC) {
            $self = new self();
            $self->isLogin = true;
            return $self;
        }
        return null;
    }

    public function getId(): ?string
    {
        return $this->isLogin ? self::MOCK_ID : null;
    }

    public function refreshIdentity()
    {
        $this->isRefreshed = true;
        return $this;
    }

    public function getIsRefreshed(): bool
    {
        return $this->isRefreshed;
    }
}
