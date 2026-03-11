<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\ResponsePropertyInBody;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

class ControllerForXSchemaRequestBodyProperty
{
    #[OA\Post(
        path: '/post/schema-body-property',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => ControllerForXSchemaRequestBodyPropertySchema::class,
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
    #[OA\Property]
    #[RequestPropertyInHeader(name: 'X-File-Key')]
    public string $key;

    /**
     * @example base64
     */
    #[OA\Property]
    #[RequestPropertyInHeader(name: 'X-File-Key-Encode')]
    public ?string $keyEncode = null;

    /**
     * 文件内容
     */
    #[OA\Property]
    #[ResponsePropertyInBody]
    public string $content = '';
}
