<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec;

use OpenApi\Spec as OA;
use WebmanTech\DTO\Attributes\RequestPropertyInBody;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\ResponsePropertyInBody;
use WebmanTech\DTO\Attributes\ResponsePropertyInHeader;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;

final class DocumentedRequest extends BaseRequestDTO
{
    /**
     * @var string Request token
     * @deprecated
     * @example false
     */
    #[RequestPropertyInHeader(name: 'X-Token')]
    public string $token;

    /**
     * Request content
     * @deprecated
     * @example request bytes
     */
    #[RequestPropertyInBody]
    public string $content;

    /**
     * Explicit documentation must take precedence
     * @deprecated
     * @example 456
     */
    #[RequestPropertyInHeader(name: 'X-Explicit')]
    #[OA\Property(schema: new OA\Schema(description: null, deprecated: false, example: '123'))]
    public int $explicit;

    public function handle(): DocumentedResponse
    {
        return new DocumentedResponse();
    }
}

final class DocumentedResponse extends BaseResponseDTO
{
    public function __construct(
        /**
         * Response count
         * @deprecated
         * @example 123
         */
        #[ResponsePropertyInHeader(name: 'X-Count')]
        public int $count = 0,

        /**
         * Response content
         * @deprecated
         * @example response bytes
         */
        #[ResponsePropertyInBody]
        public string $content = '',
    ) {
    }
}
