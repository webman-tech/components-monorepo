<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\RequestPropertyInPath;
use WebmanTech\DTO\Attributes\ResponsePropertyInBody;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ControllerForXSchemaRequestBodyProperty
{
    #[OA\Post(
        path: '/post/schema-body-property',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestBodyPropertySchema::class . '@handle',
        ],
    )]
    public function post()
    {
    }
}

#[OA\Schema]
class ControllerForXSchemaRequestBodyPropertySchema extends BaseRequestDTO
{
    /**
     * @example file.txt
     */
    #[RequestPropertyInHeader(name: 'X-File-Key')]
    public string $key;

    /**
     * @example base64
     */
    #[RequestPropertyInPath(name: 'encode')]
    public ?string $keyEncode = null;

    /**
     * 文件内容
     */
    #[ResponsePropertyInBody]
    public string $content = '';

    public function handle(): ControllerForXSchemaRequestBodyPropertySchemaResult
    {
        return new ControllerForXSchemaRequestBodyPropertySchemaResult(
            key: '123'
        );
    }
}

#[OA\Schema]
class ControllerForXSchemaRequestBodyPropertySchemaResult extends BaseResponseDTO
{
    #[RequestPropertyInHeader(name: 'X-File-Key')]
    public string $key;

    #[ResponsePropertyInBody]
    public string $content;
}
