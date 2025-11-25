<?php

namespace WebmanTech\CommonUtils\Testing;

use WebmanTech\CommonUtils\Request\RequestInterface;

final class TestRequest implements RequestInterface
{
    private static ?RequestInterface $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function clear(): void
    {
        self::$instance = null;
    }

    private array $data = [
        'method' => 'GET',
        'path' => '/',
        'contentType' => '',
        'query' => [],
        'pathParams' => [],
        'headers' => [],
        'cookies' => [],
        'rawBody' => '',
        'postForm' => [],
        'postJson' => [],
        'userIp' => '127.0.0.1',
    ];

    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function setGet(string $key, mixed $value): void
    {
        $this->data['query'][$key] = $value;
    }

    public function setPost(string $key, mixed $value): void
    {
        $this->data['postForm'][$key] = $value;
    }

    public function setHeader(string $key, mixed $value): void
    {
        $this->data['headers'][$key] = $value;
    }

    public function getMethod(): string
    {
        return strtoupper($this->data['method']);
    }

    public function getPath(): string
    {
        return $this->data['path'];
    }

    public function getContentType(): string
    {
        return strtolower($this->data['contentType']);
    }

    public function get(string $key): null|string|array
    {
        $query = $this->data['query'];

        return $query[$key] ?? null;
    }

    public function post(string $key): null|string|array|object
    {
        return $this->postForm($key) ?? $this->postJson($key);
    }

    public function path(string $key): null|string
    {
        $params = $this->data['pathParams'];
        $value = $params[$key] ?? null;

        return $value === null ? null : (string)$value;
    }

    public function header(string $key): ?string
    {
        $headers = $this->normalizeKeyedArray($this->data['headers']);
        $value = $headers[strtolower($key)] ?? null;

        return $value === null ? null : (string)$value;
    }

    public function cookie(string $name): ?string
    {
        $cookies = $this->data['cookies'];
        $value = $cookies[$name] ?? null;

        return $value === null ? null : (string)$value;
    }

    public function rawBody(): string
    {
        return $this->data['rawBody'];
    }

    public function postForm(string $key): null|string|array|object
    {
        $post = $this->data['postForm'];

        return $post[$key] ?? null;
    }

    public function postJson(string $key): null|string|int|float|bool|array
    {
        $json = $this->data['postJson'];

        return $json[$key] ?? null;
    }

    public function allGet(): array
    {
        return $this->data['query'];
    }

    public function allPostForm(): array
    {
        return $this->data['postForm'];
    }

    public function allPostJson(): array
    {
        return $this->data['postJson'];
    }

    public function getUserIp(): ?string
    {
        return $this->data['userIp'];
    }

    private function normalizeKeyedArray(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        return $normalized;
    }
}
