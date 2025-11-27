<?php

namespace WebmanTech\CommonUtils;

use Symfony\Component\HttpFoundation\Request as ComponentSymfonyRequest;
use Webman\Http\Request as ComponentWebmanRequest;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use WebmanTech\CommonUtils\Route\RouteObject;

final class Request
{
    private const CUSTOM_DATA_KEY = '__request_custom_data';

    private mixed $request;
    private bool $symfonyJsonParsed = false;
    private array $symfonyJsonPayload = [];

    public static function getCurrent(): ?self
    {
        $request = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_REQUEST) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_REQUEST),
            Runtime::isWebman() => \request(),
            Runtime::isLaravel() => \request(),
            function_exists('request') => \request(),
            class_exists(ComponentSymfonyRequest::class) => ComponentSymfonyRequest::createFromGlobals(),
            default => throw new UnsupportedRuntime(),
        };

        if ($request === null) {
            return null;
        }

        return new self($request);
    }

    public static function from(mixed $request): self
    {
        return $request === null
            ? self::getCurrent()
            : new self($request);
    }

    public function __construct(mixed $request)
    {
        $this->request = $request instanceof self ? $request->getOriginalRequest() : $request;
    }

    public function getOriginalRequest(): mixed
    {
        return $this->request;
    }

    /**
     * 获取请求方法，必须是大写的
     */
    public function getMethod(): string
    {
        $value = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->method(),
            $this->request instanceof ComponentSymfonyRequest => $this->request->getMethod(),
            method_exists($this->request, 'getMethod') => $this->request->getMethod(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return strtoupper($value ?? 'GET');
    }

    /**
     * 获取请求的路径
     */
    public function getPath(): string
    {
        $path = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->path(),
            $this->request instanceof ComponentSymfonyRequest => $this->request->getPathInfo(),
            method_exists($this->request, 'getPath') => $this->request->getPath(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        if ($path === '' || $path === null) {
            return '/';
        }

        return $path;
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
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->get($key),
            $this->request instanceof ComponentSymfonyRequest => $this->request->query->get($key),
            method_exists($this->request, 'get') => $this->request->get($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 中的某个参数，包含 form 和 json 中的
     */
    public function post(string $key): null|string|array|object
    {
        $value = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->post($key),
            $this->request instanceof ComponentSymfonyRequest => $this->symfonyPostForm($key),
            method_exists($this->request, 'post') => $this->request->post($key),
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
        $value = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->route?->param($key),
            $this->request instanceof ComponentSymfonyRequest => $this->request->attributes->get($key),
            method_exists($this->request, 'path') => $this->request->path($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * 获取 header 上的某个参数，如果有多个值，只返回第一个
     */
    public function header(string $key): ?string
    {
        $value = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->header($key),
            $this->request instanceof ComponentSymfonyRequest => $this->request->headers->get($key),
            method_exists($this->request, 'header') => $this->request->header($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return $value === null ? null : (string)$value;
    }

    /**
     * 获取 cookie 上的某个参数
     */
    public function cookie(string $name): ?string
    {
        $value = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->cookie($name),
            $this->request instanceof ComponentSymfonyRequest => $this->request->cookies->get($name),
            method_exists($this->request, 'cookie') => $this->request->cookie($name),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };

        return $value === null ? null : (string)$value;
    }

    /**
     * 获取 body 上的原始内容
     */
    public function rawBody(): string
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->rawBody(),
            $this->request instanceof ComponentSymfonyRequest => ($this->request->getContent() ?: ''),
            method_exists($this->request, 'rawBody') => $this->request->rawBody(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post form 上的某个参数
     */
    public function postForm(string $key): null|string|array|object
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->post($key) ?? $this->request->file($key),
            $this->request instanceof ComponentSymfonyRequest => $this->symfonyPostForm($key),
            method_exists($this->request, 'postForm') => $this->request->postForm($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post json 上的某个参数
     */
    public function postJson(string $key): null|string|int|float|bool|array
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
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->get() ?? [],
            $this->request instanceof ComponentSymfonyRequest => $this->request->query->all(),
            method_exists($this->request, 'allGet') => $this->request->allGet(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 上的所有 form 参数
     * @return array<string, string|array>
     */
    public function allPostForm(): array
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->mergeWebmanPostForm(),
            $this->request instanceof ComponentSymfonyRequest => $this->mergeSymfonyPostForm(),
            method_exists($this->request, 'allPostForm') => $this->request->allPostForm(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取 post 上的所有 json 参数
     * @return array<string, string|array>
     */
    public function allPostJson(): array
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->post() ?? [],
            $this->request instanceof ComponentSymfonyRequest => $this->symfonyJsonBody(),
            method_exists($this->request, 'allPostJson') => $this->request->allPostJson(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取用户 IP，仅返回一个
     */
    public function getUserIp(): ?string
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->getRealIp(false),
            $this->request instanceof ComponentSymfonyRequest => $this->symfonyUserIp(),
            method_exists($this->request, 'getUserIp') => $this->request->getUserIp(),
            default => null,
        };
    }

    /**
     * 获取访问的域名
     */
    public function getHost(): string
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->host(),
            $this->request instanceof ComponentSymfonyRequest => $this->request->getHost(),
            method_exists($this->request, 'getHost') => $this->request->getHost(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取当前请求上到 Route 对象
     */
    public function getRoute(): ?RouteObject
    {
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->route(),
            $this->request instanceof ComponentSymfonyRequest => null,
            method_exists($this->request, 'getRoute') => $this->request->getRoute(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    /**
     * 获取当前请求的 Session 对象
     */
    public function getSession(): ?Session
    {
        $session = match (true) {
            $this->request instanceof ComponentWebmanRequest => $this->request->session(),
            $this->request instanceof ComponentSymfonyRequest => null,
            method_exists($this->request, 'getSession') => $this->request->getSession(),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
        return Session::from($session);
    }

    /**
     * 修改请求头
     */
    public function withHeaders(array $data): self
    {
        if ($this->request instanceof ComponentWebmanRequest) {
            foreach ($data as $k => $v) {
                $this->request->setHeader(strtolower($k), $v);
            }
        } else {
            match (true) {
                $this->request instanceof ComponentSymfonyRequest => $this->request->headers->add($data),
                method_exists($this->request, 'withHeaders') => $this->request->withHeaders($data),
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
        if ($this->request instanceof ComponentWebmanRequest) {
            // webman 使用动态变量的形式
            $value = $this->request->{self::CUSTOM_DATA_KEY} ?? [];
            $value = array_merge($value, $data);
            $this->request->{self::CUSTOM_DATA_KEY} = $value;
        } elseif ($this->request instanceof ComponentSymfonyRequest) {
            $value = $this->request->attributes->get(self::CUSTOM_DATA_KEY, []);
            $value = array_merge($value, $data);
            $this->request->attributes->set(self::CUSTOM_DATA_KEY, $value);
        } elseif (method_exists($this->request, 'withCustomData')) {
            $this->request->withCustomData($data);
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
        return match (true) {
            $this->request instanceof ComponentWebmanRequest => ($this->request->{self::CUSTOM_DATA_KEY} ?? [])[$key] ?? null,
            $this->request instanceof ComponentSymfonyRequest => $this->request->attributes->get(self::CUSTOM_DATA_KEY, [])[$key] ?? null,
            method_exists($this->request, 'getCustomData') => $this->request->getCustomData($key),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }

    private function symfonyPostForm(string $key): null|string|array|object
    {
        /** @phpstan-ignore-next-line */
        $post = $this->request->request->all();
        if (array_key_exists($key, $post)) {
            return $post[$key];
        }
        /** @phpstan-ignore-next-line */
        $files = $this->request->files->all();

        return $files[$key] ?? null;
    }

    private function mergeWebmanPostForm(): array
    {
        $post = $this->request->post() ?? [];
        $files = $this->request->file() ?? [];

        return array_merge($post, $files);
    }

    private function mergeSymfonyPostForm(): array
    {
        /** @phpstan-ignore-next-line */
        return array_merge(
            $this->request->request->all(),
            $this->request->files->all(),
        );
    }

    private function symfonyJsonBody(): array
    {
        if (!$this->request instanceof ComponentSymfonyRequest) {
            return [];
        }
        if ($this->symfonyJsonParsed) {
            return $this->symfonyJsonPayload;
        }
        $this->symfonyJsonParsed = true;
        $this->symfonyJsonPayload = [];
        $contentType = $this->getContentType();
        if ($contentType === '' || !str_contains($contentType, 'json')) {
            return $this->symfonyJsonPayload;
        }
        $content = $this->request->getContent();
        if ($content === false || $content === '') {
            return $this->symfonyJsonPayload;
        }
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $this->symfonyJsonPayload = $decoded;
        }

        return $this->symfonyJsonPayload;
    }

    private function symfonyUserIp(): string
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
