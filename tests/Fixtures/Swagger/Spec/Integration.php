<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\Swagger\DTO\SchemaConstants as X;

final class IntegrationController
{
    #[OA\Operation\Post(
        path: '/spec/request',
        x: [X::X_SCHEMA_REQUEST => ZeroAndDefaultForm::class . '@handle'],
    )]
    public function request(): void
    {
    }

    #[OA\Operation\Get(
        path: '/spec/unwrapped',
        x: [
            X::X_SCHEMA_RESPONSE => ZeroAndDefaultResponse::class,
            X::X_RESPONSE_LAYOUT => null,
        ],
    )]
    public function unwrapped(): void
    {
    }
}

#[OA\Tag(name: 'class-tag')]
final class MethodTagController
{
    #[OA\Tag(name: 'method-tag')]
    #[OA\Operation\Get(path: '/spec/tags', tags: ['operation-tag'])]
    public function tagged(): void
    {
    }
}

#[OA\Tag(name: 'class-tag')]
final class SharedTagController
{
    #[OA\Tag(name: 'method-tag')]
    #[OA\Operation\Get(path: '/spec/shared-tags', tags: ['operation-tag'])]
    public function tagged(): void
    {
    }
}

final class UploadForm extends BaseRequestDTO
{
    public \Webman\Http\UploadFile|string $mixed;
    public \Webman\Http\UploadFile $file;
}

#[OA\Schema(required: ['token'])]
final class ZeroAndDefaultForm extends BaseRequestDTO
{
    #[ValidationRules(min: 0, max: 0)]
    public int $count = 0;

    #[ValidationRules(minLength: 0, maxLength: 0)]
    public string $text = '';

    public bool $flag = false;

    public array $values = [];

    #[RequestPropertyInHeader(name: 'X-Required')]
    public string $token;

    public function handle(): ZeroAndDefaultResponse
    {
        return new ZeroAndDefaultResponse();
    }
}

final class ZeroAndDefaultResponse extends BaseResponseDTO
{
    public string $value = '';
}

#[OA\Schema(component: 'ResponseEnvelope')]
final class Envelope
{
    #[OA\Property]
    public int $code;
}

#[OA\Schema]
final class ExplicitSchema extends BaseRequestDTO
{
    #[OA\Property(schema: new OA\Schema(type: 'string', maxLength: 4, default: 'chosen'))]
    #[ValidationRules(maxLength: 8)]
    public string $value = 'php';
}
