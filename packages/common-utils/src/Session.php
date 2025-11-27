<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Contracts\Session\Session as IlluminateSession;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use Workerman\Protocols\Http\Session as WebmanSession;

final readonly class Session
{
    public static function getCurrent(): self
    {
        $session = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_SESSION) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_SESSION),
            Runtime::isWebman() => \request()->session(),
            Runtime::isLaravel() => \request()->session(),
            default => throw new UnsupportedRuntime(),
        };

        return new self($session);
    }

    public static function from(mixed $session): self
    {
        return $session === null
            ? self::getCurrent()
            : new self($session);
    }

    public function __construct(private mixed $session)
    {
    }

    /**
     * 获取值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return match (true) {
            $this->session instanceof WebmanSession => $this->session->get($key, $default),
            $this->session instanceof IlluminateSession => $this->session->get($key, $default),
            method_exists($this->session, 'get') => $this->session->get($key, $default),
            default => throw new \InvalidArgumentException('session has no method get'),
        };
    }

    /**
     * 设置值
     */
    public function set(string $key, mixed $value): void
    {
        match (true) {
            $this->session instanceof WebmanSession => $this->session->set($key, $value),
            $this->session instanceof IlluminateSession => $this->session->put($key, $value),
            method_exists($this->session, 'set') => $this->session->set($key, $value),
            default => throw new \InvalidArgumentException('session has no method set'),
        };
    }

    public function delete(string $key): void
    {
        match (true) {
            $this->session instanceof WebmanSession => $this->session->delete($key),
            $this->session instanceof IlluminateSession => $this->session->forget($key),
            method_exists($this->session, 'delete') => $this->session->delete($key),
            default => throw new \InvalidArgumentException('session has no method delete'),
        };
    }
}
