<?php

namespace WebmanTech\DTO\Integrations;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Webman\Http\Response as WebmanResponse;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\DTO\Helper\ConfigHelper;

/**
 * @internal
 */
final class Response
{
    private static ?ResponseInterface $factory = null;

    public static function create(): ResponseInterface
    {
        if (self::$factory === null) {
            $factory = ConfigHelper::get('dto.response_factory');
            if ($factory === null) {
                $factory = match (true) {
                    class_exists(WebmanResponse::class) => WebmanResponseFactory::class,
                    class_exists(SymfonyResponse::class) => SymfonyResponseFactory::class,
                    default => throw new InvalidArgumentException('not found response class'),
                };
            }
            if ($factory instanceof \Closure) {
                $factory = $factory();
            }
            if ($factory instanceof ResponseInterface) {
                self::$factory = $factory;
            } elseif (class_exists($factory) && is_a($factory, ResponseInterface::class, true)) {
                self::$factory = new $factory();
            } else {
                throw new InvalidArgumentException('response_factory error');
            }
        }

        return self::$factory;
    }
}

/**
 * @internal
 */
final class WebmanResponseFactory implements ResponseInterface
{
    public function json(mixed $data, BaseResponseDTO $responseDTO): WebmanResponse
    {
        $response = new WebmanResponse(
            status: $responseDTO->getResponseStatus(),
            headers: array_merge(['Content-Type' => 'application/json'], $responseDTO->getResponseHeaders()),
            body: json_encode($data) ?: '',
        );
        if ($text = $responseDTO->getResponseStatusText()) {
            $response->withStatus($response->getStatusCode(), $text);
        }
        return $response;
    }
}

/**
 * @internal
 */
final class SymfonyResponseFactory implements ResponseInterface
{
    public function json(mixed $data, BaseResponseDTO $responseDTO): SymfonyResponse
    {
        $response = new SymfonyJsonResponse($data, headers: $responseDTO->getResponseHeaders());
        $response->setStatusCode($responseDTO->getResponseStatus(), $responseDTO->getResponseStatusText());
        return $response;
    }
}
