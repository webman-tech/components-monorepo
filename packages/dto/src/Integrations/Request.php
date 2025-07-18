<?php

namespace WebmanTech\DTO\Integrations;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Webman\Http\Request as WebmanRequest;

/**
 * @internal
 * @phpstan-type TypeRequest = WebmanRequest|SymfonyRequest|PsrServerRequest|string
 */
final class Request
{
    public static function from($request): RequestInterface
    {
        return match (true) {
            $request instanceof WebmanRequest => new WebmanRequestIntegration($request),
            $request instanceof SymfonyRequest => new SymfonyRequestIntegration($request),
            default => throw new \InvalidArgumentException('Not support request type: ' . get_class($request)),
        };
    }
}

/**
 * @internal
 */
final class WebmanRequestIntegration implements RequestInterface
{
    public function __construct(private readonly WebmanRequest $request)
    {
    }

    public function getMethod(): string
    {
        return strtoupper($this->request->method() ?? 'GET');
    }

    public function getContentType(): string
    {
        return strtolower($this->request->header('Content-Type') ?? '');
    }

    public function get(string $key): null|string|array
    {
        return $this->request->get($key);
    }

    public function path(string $key): null|string
    {
        return $this->request->route?->param($key);
    }

    public function header(string $key): ?string
    {
        return $this->request->header($key);
    }

    public function cookie(string $name): ?string
    {
        return $this->request->cookie($name);
    }

    public function rawBody(): string
    {
        return $this->request->rawBody();
    }

    public function postForm(string $key): null|string|array
    {
        return $this->request->post($key);
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
        return $this->request->post();
    }

    public function allPostJson(): array
    {
        return $this->request->post();
    }
}

/**
 * @internal
 */
final class SymfonyRequestIntegration implements RequestInterface
{
    public function __construct(private readonly SymfonyRequest $request)
    {
    }

    public function getMethod(): string
    {
        return strtoupper($this->request->getMethod() ?? 'GET');
    }

    public function getContentType(): string
    {
        return strtolower($this->request->headers->get('Content-Type') ?? '');
    }

    public function get(string $key): null|string|array
    {
        return $this->request->query->get($key);
    }

    public function path(string $key): null|string
    {
        // 不支持
        return null;
    }

    public function header(string $key): ?string
    {
        return $this->request->headers->get($key);
    }

    public function cookie(string $name): ?string
    {
        return $this->request->cookies->get($name);
    }

    public function rawBody(): string
    {
        return $this->request->getContent();
    }

    public function postForm(string $key): null|string|array
    {
        return $this->request->request->get($key);
    }

    public function postJson(string $key): null|string|int|float|bool|array
    {
        return $this->request->request->get($key);
    }

    public function allGet(): array
    {
        return $this->request->query->all();
    }

    public function allPostForm(): array
    {
        return $this->request->request->all();
    }

    public function allPostJson(): array
    {
        return $this->request->request->all();
    }
}
