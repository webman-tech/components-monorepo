<?php

namespace WebmanTech\CommonUtils\Testing;

use WebmanTech\CommonUtils\Route\RouteObject;
use WebmanTech\CommonUtils\Session;

final class TestRequest
{
    private array $data = [
        'method' => 'GET',
        'path' => '/',
        'query' => [],
        'pathParams' => [],
        'headers' => [],
        'cookies' => [],
        'rawBody' => '',
        'postForm' => [],
        'postJson' => [],
        'userIp' => '127.0.0.1',
        'customData' => [],
        'route' => null,
    ];

    private static ?self $instance = null;

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

    public function setData(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setData($k, $v);
            }
            return $this;
        }
        if ($key === 'headers') {
            $this->setHeader($value);
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
    }

    public function setGet(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setGet($k, $v);
            }
            return $this;
        }
        $this->data['query'][$key] = $value;
        return $this;
    }

    public function setPost(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setPost($k, $v);
            }
            return $this;
        }
        $this->data['postForm'][$key] = $value;
        $this->data['postJson'][$key] = $value;
        return $this;
    }

    public function setHeader(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->withHeaders($key);
            return $this;
        }
        $this->data['headers'][strtolower($key)] = $value;
        return $this;
    }

    public function setRoute(?RouteObject $route): self
    {
        $this->data['route'] = $route;
        return $this;
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
        return strtolower($this->data['headers']['content-type'] ?? '');
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

    public function getHost(): string
    {
        return $this->data['headers']['host'] ?? '';
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        return $this;
    }

    public function getRoute(): ?RouteObject
    {
        return $this->data['route'];
    }

    public function getSession(): Session
    {
        return Session::getCurrent();
    }

    public function withCustomData(array $data = []): self
    {
        $this->data['customData'] = array_merge($this->data['customData'], $data);
        return $this;
    }

    /**
     * 获取自定义数据
     */
    public function getCustomData(string $key): mixed
    {
        return $this->data['customData'][$key] ?? null;
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
