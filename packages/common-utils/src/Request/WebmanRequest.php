<?php

namespace WebmanTech\CommonUtils\Request;

use Webman\Http\Request as ComponentWebmanRequest;

final class WebmanRequest implements RequestInterface
{
    public function __construct(private readonly ComponentWebmanRequest $request)
    {
    }

    public function getMethod(): string
    {
        /** @phpstan-ignore-next-line */
        return strtoupper($this->request->method() ?? 'GET');
    }

    public function getPath(): string
    {
        return $this->request->path();
    }

    public function getContentType(): string
    {
        /** @phpstan-ignore-next-line */
        return strtolower($this->request->header('Content-Type') ?? '');
    }

    public function get(string $key): null|string|array
    {
        return $this->request->get($key);
    }

    public function post(string $key): null|string|array|object
    {
        return $this->request->post($key);
    }

    public function path(string $key): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->route?->param($key);
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string)$value : null;
    }

    public function header(string $key): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->header($key);

        return $value === null ? null : (string)$value;
    }

    public function cookie(string $name): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->cookie($name);

        return $value === null ? null : (string)$value;
    }

    public function rawBody(): string
    {
        return $this->request->rawBody();
    }

    public function postForm(string $key): null|string|array|object
    {
        $value = $this->request->post($key);
        if ($value !== null) {
            return $value;
        }

        return $this->request->file($key);
    }

    public function postJson(string $key): null|string|int|float|bool|array
    {
        return $this->request->post($key);
    }

    public function allGet(): array
    {
        return $this->request->get();
    }

    public function allPostForm(): array
    {
        $post = $this->request->post() ?? [];
        $files = $this->request->file() ?? [];

        return array_merge($post, $files);
    }

    public function allPostJson(): array
    {
        return $this->request->post() ?? [];
    }

    public function getUserIp(): ?string
    {
        return $this->request->getRealIp(false);
    }
}
