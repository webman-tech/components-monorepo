<?php

use Webman\Http\Response;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\DTO\Helper\ConfigHelper;

test('toResponse with format', function () {
    class DTOToResponseWithFormat extends BaseResponseDTO
    {
        public function __construct(
            public readonly string $name,
        )
        {
        }
    }

    // 默认 json
    $dto = new DTOToResponseWithFormat(name: 'nameValue');
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getHeader('Content-Type'))->toBe('application/json');

    // 通过 config 配置
    ConfigHelper::setForTest('dto.to_response_format', function (array $data) {
        return response(json_encode($data), 201);
    });
    $dto = new DTOToResponseWithFormat(name: 'nameValue');
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(201);

    // 重置
    ConfigHelper::setForTest();
});
