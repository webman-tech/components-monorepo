<?php

use Webman\Http\Response;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\DTO\Helper\ConfigHelper;

test('不同 format', function () {
    class DTOToResponseForFormat extends BaseResponseDTO
    {
        public function __construct(
            public readonly string $name,
        )
        {
        }
    }

    // 默认 json
    $dto = new DTOToResponseForFormat(name: 'nameValue');
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getHeader('Content-Type'))->toBe('application/json');

    // 通过 config 配置
    ConfigHelper::setForTest('dto.to_response_format', function (array $data) {
        return response(json_encode($data), 201);
    });
    $dto = new DTOToResponseForFormat(name: 'nameValue');
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(201);

    // 重置
    ConfigHelper::setForTest();
});

test('json 下返回空对象时', function () {
    class DTOToResponseEmpty extends BaseResponseDTO
    {
    }

    $dto = new DTOToResponseEmpty();
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->rawBody())->toBe('{}');
});

test('传递 headers 和 status', function () {
    class DTOToResponseForHeadersAndStatus extends BaseResponseDTO
    {
        public function __construct(
            public readonly string $name,
        )
        {
        }
    }

    // 默认 json
    $dto = new DTOToResponseForHeadersAndStatus(name: 'nameValue');
    $dto->withResponseStatus(201)
        ->withResponseHeaders(['X-Test' => 'test']);
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getHeader('Content-Type'))->toBe('application/json')
        ->and($response->getHeader('X-Test'))->toBe('test')
        ->and($response->getStatusCode())->toBe(201);
});
