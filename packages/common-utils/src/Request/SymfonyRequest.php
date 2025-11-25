<?php

namespace WebmanTech\CommonUtils\Request;

use Symfony\Component\HttpFoundation\Request as ComponentSymfonyRequest;

final class SymfonyRequest implements RequestInterface
{
    private bool $jsonParsed = false;
    private array $jsonPayload = [];

    public function __construct(private readonly ComponentSymfonyRequest $request)
    {
    }

    public function getMethod(): string
    {
        return strtoupper($this->request->getMethod() ?? 'GET');
    }

    public function getPath(): string
    {
        $path = $this->request->getPathInfo();

        return $path === '' ? '/' : $path;
    }

    public function getContentType(): string
    {
        /** @phpstan-ignore-next-line */
        return strtolower($this->request->headers->get('Content-Type') ?? '');
    }

    public function get(string $key): null|string|array
    {
        /** @phpstan-ignore-next-line */
        return $this->request->query->get($key);
    }

    public function post(string $key): null|string|array|object
    {
        return $this->postForm($key) ?? $this->postJson($key);
    }

    public function path(string $key): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->attributes->get($key);
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string)$value : null;
    }

    public function header(string $key): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->headers->get($key);

        return $value === null ? null : (string)$value;
    }

    public function cookie(string $name): ?string
    {
        /** @phpstan-ignore-next-line */
        $value = $this->request->cookies->get($name);

        return $value === null ? null : (string)$value;
    }

    public function rawBody(): string
    {
        $content = $this->request->getContent();

        return $content === false ? '' : $content;
    }

    public function postForm(string $key): null|string|array|object
    {
        $post = $this->request->request->all();
        if (array_key_exists($key, $post)) {
            return $post[$key];
        }

        /** @phpstan-ignore-next-line */
        $files = $this->request->files->all();

        return $files[$key] ?? null;
    }

    public function postJson(string $key): null|string|int|float|bool|array
    {
        $payload = $this->jsonBody();

        return $payload[$key] ?? null;
    }

    public function allGet(): array
    {
        return $this->request->query->all();
    }

    public function allPostForm(): array
    {
        /** @phpstan-ignore-next-line */
        return array_merge(
            $this->request->request->all(),
            $this->request->files->all(),
        );
    }

    public function allPostJson(): array
    {
        return $this->jsonBody();
    }

    private function jsonBody(): array
    {
        if ($this->jsonParsed) {
            return $this->jsonPayload;
        }
        $this->jsonParsed = true;
        $this->jsonPayload = [];
        $contentType = $this->getContentType();
        if ($contentType === '' || !str_contains($contentType, 'json')) {
            return $this->jsonPayload;
        }
        $content = $this->request->getContent();
        if ($content === false || $content === '') {
            return $this->jsonPayload;
        }
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $this->jsonPayload = $decoded;
        }

        return $this->jsonPayload;
    }

    public function getUserIp(): string
    {
        $originalProxies = ComponentSymfonyRequest::getTrustedProxies();
        $originalHeaderSet = ComponentSymfonyRequest::getTrustedHeaderSet();

        ComponentSymfonyRequest::setTrustedProxies(
            ['0.0.0.0/0', '::/0'],
            ComponentSymfonyRequest::HEADER_X_FORWARDED_FOR
        );

        try {
            $ip = $this->request->getClientIp();
        } finally {
            ComponentSymfonyRequest::setTrustedProxies($originalProxies, $originalHeaderSet);
        }

        return $ip ?? '0.0.0.0';
    }
}
