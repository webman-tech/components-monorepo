<?php

namespace WebmanTech\DTO\Integrations;

use Closure;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Webman\Http\Request as WebmanRequest;
use Webman\Http\UploadFile;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * @internal
 * @phpstan-type TypeRequest = WebmanRequest|SymfonyRequest|PsrServerRequest|string|mixed
 */
final class Request
{
    private static null|string|Closure $instanceClass = null;

    /**
     * @param TypeRequest|null $request
     */
    public static function from(mixed $request = null): RequestInterface
    {
        if (self::$instanceClass === null) {
            /** @var string|Closure|null $instanceClass */
            $instanceClass = ConfigHelper::get('dto.request_class');
            if ($instanceClass === null) {
                $instanceClass = match (true) {
                    $request instanceof WebmanRequest => WebmanRequestIntegration::class,
                    $request instanceof SymfonyRequest => SymfonyRequestIntegration::class,
                    default => throw new \InvalidArgumentException('not found request class'),
                };
            }
            self::$instanceClass = $instanceClass;
        }

        $instanceClass = self::$instanceClass;
        return match (true) {
            $instanceClass instanceof Closure => $instanceClass($request),
            class_exists($instanceClass) && is_a($instanceClass, RequestInterface::class, true) => new $instanceClass($request),
            default => throw new \InvalidArgumentException('Not support request class'),
        };
    }

    /**
     * @internal 测试环境清理使用
     */
    public static function cleanForTest(): void
    {
        self::$instanceClass = null;
    }
}

/**
 * @internal
 */
final class WebmanRequestIntegration implements RequestInterface
{
    private WebmanRequest $request;

    public function __construct(?WebmanRequest $request = null)
    {
        /** @phpstan-ignore-next-line */
        $this->request = $request ?? request();
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

    /** @phpstan-ignore-next-line */
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
        /** @phpstan-ignore-next-line */
        return array_merge($this->request->post(), $this->request->file());
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
    private SymfonyRequest $request;

    public function __construct(?SymfonyRequest $request = null)
    {
        $this->request = $request ?? SymfonyRequest::createFromGlobals();
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
