<?php

namespace WebmanTech\CommonUtils;

use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

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

    public function __construct(private mixed $session)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->session instanceof \Workerman\Protocols\Http\Session) {
            return $this->session->get($key, $default);
        }
        if ($this->session instanceof \Illuminate\Contracts\Session\Session) {
            return $this->session->get($key, $default);
        }
        if (method_exists($this->session, 'get')) {
            return $this->session->get($key, $default);
        }
        throw new \InvalidArgumentException('session has no method get');
    }

    public function set(string $key, mixed $value): void
    {
        if ($this->session instanceof \Workerman\Protocols\Http\Session) {
            $this->session->set($key, $value);
            return;
        }
        if ($this->session instanceof \Illuminate\Contracts\Session\Session) {
            $this->session->put($key, $value);
            return;
        }
        if (method_exists($this->session, 'set')) {
            $this->session->set($key, $value);
            return;
        }
        throw new \InvalidArgumentException('session has no method set');
    }
}
