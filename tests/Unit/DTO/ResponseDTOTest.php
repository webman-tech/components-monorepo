<?php

use Webman\Http\Response;
use WebmanTech\DTO\BaseResponseDTO;

test('toResponse with defaultFormat', function () {
    class DTOToResponseWithDefaultFormat extends BaseResponseDTO
    {
        public function __construct(
            public readonly string $name,
        )
        {
        }
    }

    $dto = new DTOToResponseWithDefaultFormat(name: 'nameValue');
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getHeader('Content-Type'))->toBe('application/json');
});
