<?php

use Webman\Http\Response;
use WebmanTech\CommonUtils\Testing\TestResponse;
use WebmanTech\DTO\Attributes\ToArrayConfig;
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
    /** @var TestResponse $response */
    $response = $dto->toResponse();
    expect($response)->toBeInstanceOf(TestResponse::class)
        ->and($response->getHeader('Content-Type'))->toBe('application/json');

    // 通过 config 配置
    ConfigHelper::setForTest('dto.to_response_format', function (DTOToResponseForFormat $response) {
        return response(json_encode($response->toArray()), $response->getResponseStatus());
    });
    $dto = new DTOToResponseForFormat(name: 'nameValue');
    $dto->withResponseStatus(201);
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
    /** @var TestResponse $response */
    $response = $dto->toResponse();
    expect($response->rawBody())->toBe('{}');
});

test('json 下返回的数据中存在空数组时', function () {
    #[ToArrayConfig(emptyArrayAsObject: true)]
    class DTOToResponseEmptyData extends BaseResponseDTO
    {
        public array $arr = [];
    }

    $dto = new DTOToResponseEmptyData();
    /** @var TestResponse $response */
    $response = $dto->toResponse();
    expect($response->rawBody())->toBe('{"arr":{}}');
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
        ->withResponseStatusText('Created Abc')
        ->withResponseHeaders(['X-Test' => 'test']);
    expect($dto->getResponseStatus())->toBe(201)
        ->and($dto->getResponseStatusText())->toBe('Created Abc')
        ->and($dto->getResponseHeaders())->toBe(['X-Test' => 'test']);
    /** @var TestResponse $response */
    $response = $dto->toResponse();
    expect($response->getHeader('Content-Type'))->toBe('application/json')
        ->and($response->getHeader('X-Test'))->toBe('test')
        ->and($response->getStatusCode())->toBe(201)
        ->and($response->getReasonPhrase())->toBe('Created Abc');
});
