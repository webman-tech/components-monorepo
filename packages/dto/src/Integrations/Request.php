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
            $request instanceof PsrServerRequest => new PsrRequestIntegration($request),
            $request === 'php' => new PhpRequest(),
            default => throw new \InvalidArgumentException('Invalid request type'),
        };
    }
}

/**
 * @internal
 */
final class PhpRequest implements RequestInterface
{
    public function get(string $key): null|string|array
    {
        return $this->getAll()[$key] ?? null;
    }

    public function getAll(): array
    {
        return $_GET;
    }

    public function post(string $key): null|string|array
    {
        return $this->postAll() ?? null;
    }

    public function postAll(): array
    {
        return $_POST;
    }

    public function header(string $key): ?string
    {
        return $this->headerAll()[$key] ?? null;
    }

    public function headerAll(): array
    {
        return $_SERVER;
    }

    public function rawBody(): string
    {
        return file_get_contents('php://input');
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

    public function get(string $key): null|string|array
    {
        return $this->request->get($key);
    }

    public function getAll(): array
    {
        return $this->request->get();
    }

    public function post(string $key): null|string|array
    {
        return $this->request->post($key);
    }

    public function postAll(): array
    {
        return $this->request->post();
    }

    private function getHeaderFirstValue(?string $value): ?string
    {
        return $value ? explode(',', $value)[0] : null;
    }

    public function header(string $key): ?string
    {
        return $this->getHeaderFirstValue($this->request->header($key));
    }

    public function headerAll(): array
    {
        return array_map(fn(string $value) => $this->getHeaderFirstValue($value), $this->request->header());
    }

    public function rawBody(): string
    {
        return $this->request->rawBody();
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

    public function get(string $key): null|string|array
    {
        return $this->request->query->get($key);
    }

    public function getAll(): array
    {
        return $this->request->query->all();
    }

    public function post(string $key): null|string|array
    {
        return $this->request->request->get($key);
    }

    public function postAll(): array
    {
        return $this->request->request->all();
    }

    public function header(string $key): ?string
    {
        return $this->request->headers->get($key);
    }

    public function headerAll(): array
    {
        return array_map(fn(array $value) => $value[0], $this->request->headers->all());
    }

    public function rawBody(): string
    {
        return $this->request->getContent();
    }
}

/**
 * @internal
 */
final class PsrRequestIntegration implements RequestInterface
{
    public function __construct(private readonly PsrServerRequest $request)
    {
    }

    public function get(string $key): null|string|array
    {
        return $this->getAll()[$key] ?? null;
    }

    public function getAll(): array
    {
        return $this->request->getQueryParams();
    }

    public function post(string $key): null|string|array
    {
        return $this->postAll()[$key] ?? null;
    }

    public function postAll(): array
    {
        $parsedBody = $this->request->getParsedBody();
        if (!is_array($parsedBody)) {
            return [];
        }
        return $parsedBody;
    }

    public function header(string $key): ?string
    {
        $value = $this->request->getHeaderLine($key);
        return $value ? explode(',', $value)[0] : null;
    }

    public function headerAll(): array
    {
        return array_map(fn(array $values) => $values[0], $this->request->getHeaders());
    }

    public function rawBody(): string
    {
        return $this->request->getBody()->getContents();
    }
}
