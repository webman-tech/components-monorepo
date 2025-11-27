<?php

use WebmanTech\DTO\Attributes\RequestPropertyInHeader;
use WebmanTech\DTO\Attributes\RequestPropertyInJson;
use WebmanTech\DTO\Attributes\RequestPropertyInQuery;
use WebmanTech\DTO\BaseRequestDTO;

test('fromRequest use different method', function () {
    class DTOFromRequestUseDefaultRequestType extends BaseRequestDTO
    {
        public string $name = 'nameValue';
        public string $name2 = 'nameValue2';
    }

    $request = request_create_one();
    $originalRequest = request_get_original($request);
    $originalRequest->setGet('name', 'newNameValue');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request);
    expect($dto->name)->toBe('newNameValue')
        ->and($dto->name2)->toBe('nameValue2');

    $request = request_create_one();
    $originalRequest = request_get_original($request);
    $originalRequest->setData('method', 'POST');
    $originalRequest->setHeader('content-type', 'application/json');
    $originalRequest->setPost('name', 'newNameValue2');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request);
    expect($dto->name)->toBe('newNameValue2')
        ->and($dto->name2)->toBe('nameValue2');

    $request = request_create_one();
    $originalRequest = request_get_original($request);
    $originalRequest->setData(['method' => 'POST']);
    $originalRequest->setHeader('content-type', 'multipart/form-data');
    $originalRequest->setPost('name', 'newNameValue2');
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
    $originalRequest = request_get_original($request);
    $originalRequest->setGet([
        'name' => 'nameGetValue',
        'name2' => 'name2GetValue',
        'name3' => 'name3GetValue',
        'new_key1' => 'name4GetValue',
        'new_key2' => 'name5GetValue',
    ]);
    $originalRequest->setPost([
        'name' => 'namePostValue',
        'name2' => 'name2PostValue',
        'name3' => 'name3PostValue',
        'new_key1' => 'name4PostValue',
        'new_key2' => 'name5PostValue',
    ]);
    $originalRequest->setHeader([
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
