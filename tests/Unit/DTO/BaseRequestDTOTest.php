<?php

use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\RequestPropertyInJson;
use WebmanTech\DTO\Attributes\RequestPropertyInQuery;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Integrations\Request;
use WebmanTech\DTO\Integrations\RequestInterface;

test('fromRequest use different method', function () {
    class DTOFromRequestUseDefaultRequestType extends BaseRequestDTO
    {
        public string $name = 'nameValue';
        public string $name2 = 'nameValue2';
    }

    $request = request_create_one();
    $request->setGet('name', 'newNameValue');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request);
    expect($dto->name)->toBe('newNameValue')
        ->and($dto->name2)->toBe('nameValue2');

    $request = request_create_one();
    $request->setData('method', 'POST');
    $request->setHeader('content-type', 'application/json');
    $request->setPost('name', 'newNameValue2');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request);
    expect($dto->name)->toBe('newNameValue2')
        ->and($dto->name2)->toBe('nameValue2');

    $request = request_create_one();
    $request->setData('method', 'POST');
    $request->setHeader('content-type', 'multipart/form-data');
    $request->setPost('name', 'newNameValue2');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request);
    expect($dto->name)->toBe('newNameValue2')
        ->and($dto->name2)->toBe('nameValue2');
});

test('fromRequest with RequestPropertyIn', function () {
    class DTOFromRequestWithConfigRequestKeyFrom extends BaseRequestDTO
    {
        #[RequestPropertyInQuery]
        public string $name;
        #[RequestPropertyInJson]
        public string $name2;
        #[RequestPropertyInHeader]
        public string $name3;
        #[RequestPropertyInQuery(name: 'new_key1')]
        public string $name4;
        #[RequestPropertyInJson(name: 'new_key2')]
        public string $name5;
    }

    $request = request_create_one();
    $request->setGet([
        'name' => 'nameGetValue',
        'name2' => 'name2GetValue',
        'name3' => 'name3GetValue',
        'new_key1' => 'name4GetValue',
        'new_key2' => 'name5GetValue',
    ]);
    $request->setPost([
        'name' => 'namePostValue',
        'name2' => 'name2PostValue',
        'name3' => 'name3PostValue',
        'new_key1' => 'name4PostValue',
        'new_key2' => 'name5PostValue',
    ]);
    $request->setHeader([
        'name' => 'nameHeaderValue',
        'name2' => 'name2HeaderValue',
        'name3' => 'name3HeaderValue',
        'new_key1' => 'name4HeaderValue',
        'new_key2' => 'name5HeaderValue',
    ]);
    $dto = DTOFromRequestWithConfigRequestKeyFrom::fromRequest($request);
    expect($dto->name)->toBe('nameGetValue')
        ->and($dto->name2)->toBe('name2PostValue')
        ->and($dto->name3)->toBe('name3HeaderValue')
        ->and($dto->name4)->toBe('name4GetValue')
        ->and($dto->name5)->toBe('name5PostValue');
});

test('fromRequest with config requestInstance', function () {
    Request::cleanForTest();

    class DTOFromRequestWithConfigRequestInstance extends BaseRequestDTO
    {
        public string $name;
    }

    ConfigHelper::setForTest('dto.request_class', function () {
        return new class implements RequestInterface {
            private array $request;

            public function __construct()
            {
                $this->request = [
                    'get' => [
                        'name' => 'abc',
                    ],
                ];
            }

            public function getMethod(): string
            {
                return 'GET';
            }

            public function getContentType(): string
            {
            }

            public function get(string $key): null|string|array
            {
                return $this->request['get'][$key] ?? null;
            }

            public function path(string $key): null|string
            {
            }

            public function header(string $key): ?string
            {
            }

            public function cookie(string $name): ?string
            {
            }

            public function rawBody(): string
            {
            }

            public function postForm(string $key): null|string|array|object
            {
            }

            public function postJson(string $key): null|string|int|float|bool|array
            {
            }

            public function allGet(): array
            {
                return $this->request['get'];
            }

            public function allPostForm(): array
            {
            }

            public function allPostJson(): array
            {
            }
        };
    });

    $dto = DTOFromRequestWithConfigRequestInstance::fromRequest();
    expect($dto->name)->toBe('abc');

    ConfigHelper::setForTest('dto.request_class');
    Request::cleanForTest();
});
