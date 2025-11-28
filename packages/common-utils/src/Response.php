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

    public static function from(object|null $response): self
    {
        return match (true) {
            $response instanceof self => $response,
            $response === null => self::make(),
            default => new self($response),
        };
    }

    public function __construct(private object $response)
    {
    }

    /**
     * 获取原始响应对象
     */
    public function getRaw(): object
    {
        return $this->response;
    }

    /**
     * 设置响应状态码
     */
    public function withStatus(int $statusCode, ?string $reasonPhrase = null): self
    {
        $response = $this->response;
        match (true) {
            $response instanceof WebmanResponse => $response->withStatus($statusCode, $reasonPhrase),
            $response instanceof SymfonyResponse => $response->setStatusCode($statusCode, $reasonPhrase),
            method_exists($response, 'withStatus') => $response->withStatus($statusCode, $reasonPhrase),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 添加响应头
     */
    public function withHeaders(array $headers): self
    {
        $response = $this->response;
        match (true) {
            $response instanceof WebmanResponse => $response->withHeaders($headers),
            $response instanceof SymfonyResponse => $response->headers->add($headers),
            method_exists($response, 'withHeaders') => $response->withHeaders($headers),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 添加响应内容
     */
    public function withBody(string $content): self
    {
        $response = $this->response;
        match (true) {
            $response instanceof WebmanResponse => $response->withBody($content),
            $response instanceof SymfonyResponse => $response->setContent($content),
            method_exists($response, 'withBody') => $response->withBody($content),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
        return $this;
    }

    /**
     * 获取响应状态码
     */
    public function getStatusCode(): int
    {
        $response = $this->response;
        return match (true) {
            $response instanceof WebmanResponse => $response->getStatusCode(),
            $response instanceof SymfonyResponse => $response->getStatusCode(),
            method_exists($response, 'getStatusCode') => $response->getStatusCode(),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }

    /**
     * 获取响应头，如果有多个，仅返回第一个，不区分 key 大小写
     */
    public function getHeader(string $key, mixed $default = null): ?string
    {
        $response = $this->response;
        return match (true) {
            $response instanceof WebmanResponse => $response->getHeader($key) ?? $default,
            $response instanceof SymfonyResponse => $response->headers->get($key, $default),
            method_exists($response, 'getHeader') => $response->getHeader($key, $default),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }

    /**
     * 获取响应内容
     */
    public function getBody(): string
    {
        $response = $this->response;
        return match (true) {
            $response instanceof WebmanResponse => $response->rawBody(),
            $response instanceof SymfonyResponse => $response->getContent(),
            method_exists($response, 'getBody') => $response->getBody(),
            default => throw new \InvalidArgumentException('Unsupported response type'),
        };
    }
}
