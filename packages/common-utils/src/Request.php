<?php

namespace WebmanTech\CommonUtils;

use Symfony\Component\HttpFoundation\Request as ComponentSymfonyRequest;
use Webman\Http\Request as ComponentWebmanRequest;
use WebmanTech\CommonUtils\Exceptions\UnsupportedRuntime;
use WebmanTech\CommonUtils\Request\RequestInterface;
use WebmanTech\CommonUtils\Request\SymfonyRequest;
use WebmanTech\CommonUtils\Request\WebmanRequest;

final class Request
{
    public static function getCurrent(): ?RequestInterface
    {
        $request = match (true) {
            RuntimeCustomRegister::isRegistered(RuntimeCustomRegister::KEY_REQUEST) => RuntimeCustomRegister::call(RuntimeCustomRegister::KEY_REQUEST),
            Runtime::isWebman() => \request(),
            Runtime::isLaravel() => \request(),
            function_exists('request') => \request(),
            class_exists(ComponentSymfonyRequest::class) => ComponentSymfonyRequest::createFromGlobals(),
            default => throw new UnsupportedRuntime(),
        };

        return self::wrapper($request);
    }

    public static function wrapper(mixed $request): RequestInterface
    {
        return match (true) {
            $request instanceof RequestInterface => $request,
            $request instanceof ComponentWebmanRequest => new WebmanRequest($request),
            $request instanceof ComponentSymfonyRequest => new SymfonyRequest($request),
            default => throw new \InvalidArgumentException('Unsupported request type'),
        };
    }
}
