<?php

namespace WebmanTech\CommonUtils;

use Illuminate\Http\Request as LaravelRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Webman\Http\Request as WebmanRequest;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use WebmanTech\CommonUtils\Route\RouteObject;

final class Request
{
    public const CUSTOM_DATA_KEY = '__request_custom_data';

    private object $request;
    private bool $symfonyJsonParsed = false;
    private array $symfonyJsonPayload = [];

    public static function getCurrent(): ?self
    {
        $request = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_REQUEST) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_REQUEST),
            Runtime::isWebman() => \request(),
            Runtime::isLaravel() => \request(),
            function_exists('request') => \request(),
            class_exists(SymfonyRequest::class) => SymfonyRequest::createFromGlobals(),
            default => throw new UnsupportedRuntime(),
        };

        if ($request === null) {
            return null;
        }

        return new self($request);
    }

    public static function from(object|null $request): self
    {
        return match (true) {
            $request instanceof self => $request,
            $request === null => self::getCurrent() ?? throw new \InvalidArgumentException('Request is null'),
            default => new self($request),
        };
    }

    public function __construct(object $request)
    {
        $this->request = $request instanceof self
            ? $request->getRaw()
            : $request;
    }

    public function getRaw(): object
    {
        return $this->request;
    }

    /**
     * 获取请求方法，必须是大写的
     */
    public function getMethod(): string
    {
        $request = $this->request;
        $value = match (true) {
            $request instanceof WebmanRequest => $request->method(),
            $request instanceof SymfonyRequest => $request->getMethod(),
            method_exists($request, 'getMethod') => $request->getMethod(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return strtoupper($value ?? 'GET');
    }

    /**
     * 获取请求的路径
     */
    public function getPath(): string
    {
        $request = $this->request;
        $path = match (true) {
            $request instanceof WebmanRequest => $request->path(),
            $request instanceof SymfonyRequest => $request->getPathInfo(),
            method_exists($request, 'getPath') => $request->getPath(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        if ($path === '' || $path === null) {
            return '/';
        }

        return $path;
    }

    /**
     * 获取请求的前缀
     */
    public function getPathPrefix(): string
    {
        $request = $this->request;
        return match (true) {
            method_exists($request, 'getPrefix') => $request->getPrefix(),
            default => $this->header('x-forwarded-prefix') ?? '',
        };
    }

    /**
     * 获取请求类型，全部转为小写
     */
    public function getContentType(): string
    {
        $value = $this->header('Content-Type') ?? '';
        return strtolower($value);
    }

    /**
     * 获取 query 上的某个参数
     */
    public function get(string $key): null|string|array
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->get($key),
            $request instanceof SymfonyRequest => $request->query->get($key),
            method_exists($request, 'get') => $request->get($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 中的某个参数，包含 form 和 json 中的
     */
    public function post(string $key): mixed
    {
        $request = $this->request;
        $value = match (true) {
            $request instanceof WebmanRequest => $request->post($key),
            $request instanceof SymfonyRequest => $this->symfonyPostForm($request, $key),
            method_exists($request, 'post') => $request->post($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        if ($value !== null) {
            return $value;
        }

        return $this->postForm($key) ?? $this->postJson($key);
    }

    /**
     * 获取 path 上的某个参数
     */
    public function path(string $key): ?string
    {
        $request = $this->request;
        $value = match (true) {
            $request instanceof WebmanRequest => $request->route?->param($key),
            $request instanceof SymfonyRequest => $request->attributes->get('_route_params', [])[$key] ?? null,
            method_exists($request, 'path') => $request->path($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * 获取 header 上的某个参数，如果有多个值，只返回第一个
     */
    public function header(string $key): ?string
    {
        $request = $this->request;
        $value = match (true) {
            $request instanceof WebmanRequest => $request->header($key),
            $request instanceof SymfonyRequest => $request->headers->get($key),
            method_exists($request, 'header') => $request->header($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return $value === null ? null : (string)$value;
    }

    /**
     * 获取 cookie 上的某个参数
     */
    public function cookie(string $name): ?string
    {
        $request = $this->request;
        $value = match (true) {
            $request instanceof WebmanRequest => $request->cookie($name),
            $request instanceof SymfonyRequest => $request->cookies->get($name),
            method_exists($request, 'cookie') => $request->cookie($name),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return $value === null ? null : (string)$value;
    }

    /**
     * 获取 body 上的原始内容
     */
    public function rawBody(): string
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->rawBody(),
            $request instanceof SymfonyRequest => ($request->getContent() ?: ''),
            method_exists($request, 'rawBody') => $request->rawBody(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post form 上的某个参数
     */
    public function postForm(string $key): mixed
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->post($key) ?? $request->file($key),
            $request instanceof SymfonyRequest => $this->symfonyPostForm($request, $key),
            method_exists($request, 'postForm') => $request->postForm($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post json 上的某个参数
     */
    public function postJson(string $key): mixed
    {
        $payload = $this->allPostJson();

        return $payload[$key] ?? null;
    }

    /**
     * 获取 query 上的所有参数
     * @return array<string, string|array>
     */
    public function allGet(): array
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->get() ?? [],
            $request instanceof SymfonyRequest => $request->query->all(),
            method_exists($request, 'allGet') => $request->allGet(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 上的所有 form 参数
     * @return array<string, string|array>
     */
    public function allPostForm(): array
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $this->mergeWebmanPostForm($request),
            $request instanceof SymfonyRequest => $this->mergeSymfonyPostForm($request),
            method_exists($request, 'allPostForm') => $request->allPostForm(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 上的所有 json 参数
     * @return array<string, string|array>
     */
    public function allPostJson(): array
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->post() ?? [],
            $request instanceof SymfonyRequest => $this->symfonyJsonBody($request),
            method_exists($request, 'allPostJson') => $request->allPostJson(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取用户 IP，仅返回一个
     */
    public function getUserIp(): ?string
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->getRealIp(false),
            $request instanceof SymfonyRequest => $this->symfonyUserIp($request),
            method_exists($request, 'getUserIp') => $request->getUserIp(),
            default => null,
        };
    }

    /**
     * 获取访问的域名
     */
    public function getHost(): string
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => $request->host(),
            $request instanceof SymfonyRequest => $request->getHost(),
            method_exists($request, 'getHost') => $request->getHost(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取当前请求上到 Route 对象
     */
    public function getRoute(): ?RouteObject
    {
        $request = $this->request;
        $route = match (true) {
            $request instanceof WebmanRequest => $request->route,
            method_exists($request, 'getRoute') => $request->getRoute(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
        return $route ? RouteObject::from($route) : null;
    }

    /**
     * 获取当前请求的 Session 对象
     */
    public function getSession(): ?Session
    {
        $request = $this->request;
        $session = match (true) {
            $request instanceof WebmanRequest => $request->session(),
            $request instanceof LaravelRequest => $request->session(),
            $request instanceof SymfonyRequest => $request->getSession(),
            method_exists($request, 'getSession') => $request->getSession(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
        return $session ? Session::from($session) : null;
    }

    /**
     * 修改请求头
     */
    public function withHeaders(array $data): self
    {
        $request = $this->request;
        if ($request instanceof WebmanRequest) {
            foreach ($data as $k => $v) {
                $request->setHeader(strtolower($k), $v);
            }
        } else {
            match (true) {
                $request instanceof SymfonyRequest => $request->headers->add($data),
                method_exists($request, 'withHeaders') => $request->withHeaders($data),
                default => throw new \InvalidArgumentException('Unsupported request type'),
            };
        }

        return $this;
    }

    /**
     * 设置自定义数据
     */
    public function withCustomData(array $data = []): self
    {
        $request = $this->request;
        if ($request instanceof WebmanRequest) {
            // webman 使用动态变量的形式
            $value = $request->{self::CUSTOM_DATA_KEY} ?? [];
            $value = array_merge($value, $data);
            /** @phpstan-ignore-next-line */
            $request->{self::CUSTOM_DATA_KEY} = $value;
        } elseif ($request instanceof SymfonyRequest) {
            $value = (array)$request->attributes->get(self::CUSTOM_DATA_KEY, []);
            $value = array_merge($value, $data);
            $request->attributes->set(self::CUSTOM_DATA_KEY, $value);
        } elseif (method_exists($request, 'withCustomData')) {
            $request->withCustomData($data);
        } else {
            throw new \InvalidArgumentException('Unsupported request type');
        }
        return $this;
    }

    /**
     * 获取自定义数据
     */
    public function getCustomData(string $key): mixed
    {
        $request = $this->request;
        return match (true) {
            $request instanceof WebmanRequest => ($request->{self::CUSTOM_DATA_KEY} ?? [])[$key] ?? null,
            $request instanceof SymfonyRequest => $request->attributes->get(self::CUSTOM_DATA_KEY, [])[$key] ?? null,
            method_exists($request, 'getCustomData') => $request->getCustomData($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    private function symfonyPostForm(SymfonyRequest $request, string $key): null|string|array|object
    {
        $post = $request->request->all();
        if (array_key_exists($key, $post)) {
            return $post[$key];
        }
        $files = $request->files->all();

        return $files[$key] ?? null;
    }

    private function mergeWebmanPostForm(WebmanRequest $request): array
    {
        $post = (array)$request->post();
        $files = (array)$request->file();

        return array_merge($post, $files);
    }

    private function mergeSymfonyPostForm(SymfonyRequest $request): array
    {
        /** @phpstan-ignore-next-line */
        return array_merge(
            $request->request->all(),
            $request->files->all(),
        );
    }

    private function symfonyJsonBody(SymfonyRequest $request): array
    {
        if ($this->symfonyJsonParsed) {
            return $this->symfonyJsonPayload;
        }
        $this->symfonyJsonParsed = true;
        $this->symfonyJsonPayload = [];
        $contentType = $this->getContentType();
        if ($contentType === '' || !str_contains($contentType, 'json')) {
            return $this->symfonyJsonPayload;
        }
        $content = $request->getContent();
        if ($content === '') {
            return $this->symfonyJsonPayload;
        }
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $this->symfonyJsonPayload = $decoded;
        }

        return $this->symfonyJsonPayload;
    }

    private function symfonyUserIp(SymfonyRequest $request): string
    {
        $originalProxies = SymfonyRequest::getTrustedProxies();
        $originalHeaderSet = SymfonyRequest::getTrustedHeaderSet();

        SymfonyRequest::setTrustedProxies(
            ['0.0.0.0/0', '::/0'],
            SymfonyRequest::HEADER_X_FORWARDED_FOR
        );

        try {
            $ip = $request->getClientIp();
        } finally {
            /** @phpstan-ignore-next-line */
            SymfonyRequest::setTrustedProxies($originalProxies, $originalHeaderSet);
        }

        return $ip ?? '0.0.0.0';
    }
}
