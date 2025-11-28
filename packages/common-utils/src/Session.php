<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Contracts\Session\Session as IlluminateSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SymfonySession;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use Workerman\Protocols\Http\Session as WebmanSession;

final readonly class Session
{
    public static function getCurrent(): self
    {
        $session = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_SESSION) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_SESSION),
            Runtime::isWebman() => \request()?->session(),
            Runtime::isLaravel() => \request()?->session(),
            function_exists('session') => session(),
            default => throw new UnsupportedRuntime(),
        };
        if ($session === null) {
            throw new \InvalidArgumentException('session cant be null');
        }

        return new self($session);
    }

    public static function from(object|null $session): self
    {
        return match (true) {
            $session instanceof self => $session,
            $session === null => self::getCurrent(),
            default => new self($session),
        };
    }

    public function __construct(private object $session)
    {
    }

    public function getRaw(): object
    {
        return $this->session;
    }

    /**
     * 获取值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $session = $this->session;
        return match (true) {
            $session instanceof WebmanSession => $session->get($key, $default),
            $session instanceof SymfonySession => $session->get($key, $default),
            $session instanceof IlluminateSession => $session->get($key, $default),
            method_exists($session, 'get') => $session->get($key, $default),
            default => throw new \InvalidArgumentException('session has no method get'),
        };
    }

    /**
     * 设置值
     */
    public function set(string $key, mixed $value): void
    {
        $session = $this->session;
        match (true) {
            $session instanceof WebmanSession => $session->set($key, $value),
            $session instanceof SymfonySession => $session->set($key, $value),
            $session instanceof IlluminateSession => $session->put($key, $value),
            method_exists($session, 'set') => $session->set($key, $value),
            default => throw new \InvalidArgumentException('session has no method set'),
        };
    }

    public function delete(string $key): void
    {
        $session = $this->session;
        match (true) {
            $session instanceof WebmanSession => $session->delete($key),
            $session instanceof SymfonySession => $session->remove($key),
            $session instanceof IlluminateSession => $session->forget($key),
            method_exists($session, 'delete') => $session->delete($key),
            default => throw new \InvalidArgumentException('session has no method delete'),
        };
    }
}
