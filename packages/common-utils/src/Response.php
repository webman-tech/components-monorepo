<?php

namespace WebmanTech\CommonUtils;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Webman\Http\Response as WebmanResponse;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;

final readonly class Response
{
    public static function make(): self
    {
        $response = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_RESPONSE) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_RESPONSE),
            Runtime::isWebman() => new WebmanResponse(),
            Runtime::isLaravel(), class_exists(SymfonyResponse::class) => new SymfonyResponse(),
            default => throw new UnsupportedRuntime(),
        };

        return new self($response);
    }

    public static function from(mixed $response): self
    {
        return $response === null
            ? self::make()
            : new self($response);
    }

    public function __construct(private mixed $response)
    {
    }

    /**
     * 获取原始响应对象
     */
    public function toRaw(): mixed
    {
        return $this->response;
    }

    /**
     * 设置响应状态码
     */
    public function withStatus(int $statusCode, ?string $reasonPhrase = null): self
    {
        match (true) {
            $this->response instanceof WebmanResponse => $this->response->withStatus($statusCode, $reasonPhrase),
            $this->response instanceof SymfonyResponse => $this->response->setStatusCode($statusCode, $reasonPhrase),
            method_exists($this->response, 'withStatus') => $this->response->withStatus($statusCode, $reasonPhrase),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 添加响应头
     */
    public function withHeaders(array $headers): self
    {
        match (true) {
            $this->response instanceof WebmanResponse => $this->response->withHeaders($headers),
            $this->response instanceof SymfonyResponse => $this->response->headers->add($headers),
            method_exists($this->response, 'withHeaders') => $this->response->withHeaders($headers),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 添加响应内容
     */
    public function withBody(string $content): self
    {
        match (true) {
            $this->response instanceof WebmanResponse => $this->response->withBody($content),
            $this->response instanceof SymfonyResponse => $this->response->setContent($content),
            method_exists($this->response, 'withBody') => $this->response->withBody($content),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 获取响应状态码
     */
    public function getStatusCode(): int
    {
        return match (true) {
            $this->response instanceof WebmanResponse => $this->response->getStatusCode(),
            $this->response instanceof SymfonyResponse => $this->response->getStatusCode(),
            method_exists($this->response, 'getStatusCode') => $this->response->getStatusCode(),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }

    /**
     * 获取响应头，如果有多个，仅返回第一个，不区分 key 大小写
     */
    public function getHeader(string $key, mixed $default = null): ?string
    {
        return match (true) {
            $this->response instanceof WebmanResponse => $this->response->getHeader($key, $default),
            $this->response instanceof SymfonyResponse => $this->response->headers->get($key, $default),
            method_exists($this->response, 'getHeader') => $this->response->getHeader($key, $default),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }

    /**
     * 获取响应内容
     */
    public function getBody(): string
    {
        return match (true) {
            $this->response instanceof WebmanResponse => $this->response->rawBody(),
            $this->response instanceof SymfonyResponse => $this->response->getContent(),
            method_exists($this->response, 'getBody') => $this->response->getBody(),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }
}
