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

    public function __construct(private mixed $response)
    {
    }

    public function sendBody(string $content, int $statusCode = 200, array $headers = [], ?string $reasonPhrase = null): mixed
    {
        if ($this->response instanceof WebmanResponse) {
            return $this->response->withStatus($statusCode, $reasonPhrase)
                ->withHeaders($headers)
                ->withBody($content);
        }
        if ($this->response instanceof SymfonyResponse) {
            $this->response->setStatusCode($statusCode, $reasonPhrase)
                ->setContent($content);
            foreach ($headers as $key => $value) {
                $this->response->headers->set($key, $value);
            }
            return $this->response;
        }
        if (method_exists($this->response, 'sendBody')) {
            return $this->response->sendBody($content, $statusCode, $headers, $reasonPhrase);
        }

        throw new \InvalidArgumentException('Unsupported response type');
    }

    public function sendJson(array $data, int $statusCode = 200, array $headers = [], ?string $reasonPhrase = null): mixed
    {
        $data = json_encode($data);
        $headers = array_merge($headers, [
            'Content-Type' => 'application/json',
        ]);

        return $this->sendBody($data, $statusCode, $headers, $reasonPhrase);
    }
}
