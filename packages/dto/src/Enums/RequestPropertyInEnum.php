<?php

namespace WebmanTech\DTO\Enums;

use WebmanTech\DTO\Integrations\RequestInterface;

enum RequestPropertyInEnum: string
{
    case Query = 'query';
    case Path = 'path';
    case Header = 'header';
    case Cookie = 'cookie';
    case Body = 'body';
    case Form = 'form';
    case Json = 'json';

    public static function tryFromRequest(RequestInterface $request): ?self
    {
        $method = $request->getMethod();
        if (in_array($method, ['GET', 'OPTIONS', 'HEAD'])) {
            return self::Query;
        }
        $contentType = $request->getContentType();
        if (str_contains($contentType, 'application/json')) {
            return self::Json;
        }
        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return self::Form;
        }
        return null;
    }

    public function getFromRequest(RequestInterface $request, string $name): mixed
    {
        return match ($this) {
            self::Query => $request->get($name),
            self::Path => $request->path($name),
            self::Header => $request->header($name),
            self::Cookie => $request->cookie($name),
            self::Body => $request->rawBody(),
            self::Form => $request->postForm($name),
            self::Json => $request->postJson($name),
        };
    }

    public function getAllFromRequest(RequestInterface $request): array
    {
        return match ($this) {
            self::Query => $request->allGet(),
            self::Json => $request->allPostJson(),
            self::Form => $request->allPostForm(),
            default => throw new \RuntimeException('not support get all from request'),
        };
    }
}
