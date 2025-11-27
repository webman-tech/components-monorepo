<?php

namespace WebmanTech\CommonUtils\Testing;

final class TestResponse
{
    private int $statusCode = 200;
    private string $reasonPhrase = 'OK';
    /**
     * @var array<string, string>
     */
    private array $headers = [];
    private string $body = '';

    public function withStatus(int $statusCode, ?string $reasonPhrase = null): self
    {
        $this->statusCode = $statusCode;
        if ($reasonPhrase !== null) {
            $this->reasonPhrase = $reasonPhrase;
        }

        return $this;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->headers[strtolower((string)$key)] = (string)$value;
        }

        return $this;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getHeader(string $name, mixed $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function rawBody(): string
    {
        return $this->body;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
