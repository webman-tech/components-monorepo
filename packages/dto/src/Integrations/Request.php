<?php

namespace WebmanTech\DTO\Integrations;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Webman\Http\Request as WebmanRequest;
use Webman\Http\UploadFile;

/**
 * @internal
 * @phpstan-type TypeRequest = WebmanRequest|SymfonyRequest|PsrServerRequest|string
 */
final class Request
{
    /**
     * @param TypeRequest $request
     */
    public static function from($request): RequestInterface
    {
        return match (true) {
            $request instanceof WebmanRequest => new WebmanRequestIntegration($request),
            $request instanceof SymfonyRequest => new SymfonyRequestIntegration($request),
            default => throw new \InvalidArgumentException('Not support request type'),
        };
    }
}

/**
 * @internal
 */
final readonly class WebmanRequestIntegration implements RequestInterface
{
    public function __construct(private WebmanRequest $request)
    {
    }

    public function getMethod(): string
    {
        /** @phpstan-ignore-next-line */
        return strtoupper($this->request->method() ?? 'GET');
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

    public function path(string $key): null|string
    {
        /** @phpstan-ignore-next-line */
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

    public function postForm(string $key): null|string|array|UploadFile
    {
        return $this->allPostForm()[$key] ?? null;
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
        return array_merge(
            $this->request->post(),
            $this->request->file(),
        );
    }

    public function allPostJson(): array
    {
        return $this->request->post();
    }
}

/**
 * @internal
 */
final readonly class SymfonyRequestIntegration implements RequestInterface
{
    public function __construct(private SymfonyRequest $request)
    {
    }

    public function getMethod(): string
    {
        /** @phpstan-ignore-next-line */
        return strtoupper($this->request->getMethod() ?? 'GET');
    }

    public function getContentType(): string
    {
        /** @phpstan-ignore-next-line */
        return strtolower($this->request->headers->get('Content-Type') ?? '');
    }

    /** @phpstan-ignore-next-line */
    public function get(string $key): null|string|array
    {
        /** @phpstan-ignore-next-line */
        return $this->request->query->get($key);
    }

    public function path(string $key): null|string
    {
        // 不支持
        return null;
    }

    public function header(string $key): ?string
    {
        /** @phpstan-ignore-next-line */
        return $this->request->headers->get($key);
    }

    public function cookie(string $name): ?string
    {
        /** @phpstan-ignore-next-line */
        return $this->request->cookies->get($name);
    }

    public function rawBody(): string
    {
        return $this->request->getContent();
    }

    /** @phpstan-ignore-next-line */
    public function postForm(string $key): null|string|array|UploadedFile
    {
        return $this->allPostForm()[$key] ?? null;
    }

    /** @phpstan-ignore-next-line */
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
        return array_merge(
            $this->request->request->all(),
            $this->request->files->all(),
        );
    }

    public function allPostJson(): array
    {
        return $this->request->request->all();
    }
}
