<?php

use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\Exceptions\DTOAssignPropertyException;
use WebmanTech\DTO\Exceptions\DTOValidateFailsException;

test('fromRequest use defaultRequestType', function () {
    class DTOFromRequestUseDefaultRequestType extends BaseRequestDTO
    {
        public string $name = 'nameValue';
        public string $name2 = 'nameValue2';
    }

    $request = request_create_one();
    $request->setGet('name', 'newNameValue');
    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request, 'get');
    expect($dto->name)->toBe('newNameValue')
        ->and($dto->name2)->toBe('nameValue2');

    $dto = DTOFromRequestUseDefaultRequestType::fromRequest($request, 'post');
    expect($dto->name)->toBe('nameValue')
        ->and($dto->name2)->toBe('nameValue2');
});

test('fromRequest with getConfigRequestKeyFrom', function () {
    class DTOFromRequestWithConfigRequestKeyFrom extends BaseRequestDTO
    {
        public string $name;
        public string $name2;
        public string $name3;
        public string $name4;
        public string $name5;

        protected function getConfigRequestKeyFrom(): array
        {
            return [
                'name' => 'get',
                'name2' => 'post',
                'name3' => 'header',
                'name4' => 'get|new_key1',
                'name5' => 'post|new_key2',
            ];
        }
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

test('init', function () {
    class DTOInit extends BaseRequestDTO
    {
        public string $name;

        public function init(): void
        {
            $this->name = 'initValue';
        }
    }

    $dto = new DTOInit();
    expect($dto->name)->toBe('initValue');
});

test('fromRequest with validate', function () {
    class DTOFromRequestWithValidate extends BaseRequestDTO
    {
        public string $name;
        public string $name2;
        public string $name3;

        protected function getConfigValidateRules(): array
        {
            return [
                'name' => 'required',
                'name2' => 'required',
            ];
        }
    }

    $request = request_create_one();
    $request->setGet([
        'name' => 'nameGetValue',
        'name2' => 'name2GetValue',
    ]);
    $dto = DTOFromRequestWithValidate::fromRequest($request, 'get');
    expect($dto->name)->toBe('nameGetValue')
        ->and($dto->name2)->toBe('name2GetValue');

    try {
        DTOFromRequestWithValidate::fromRequest($request, 'post');
    } catch (DTOValidateFailsException $e) {
        expect(array_keys($e->getErrors()))->toBe(['name', 'name2'])
            ->and($e->first())->not->toBeEmpty();
    }
});

test('fromRequest with assign type error', function () {
    class DTOFromRequestWithValidateTypeError extends BaseRequestDTO
    {
        public int $age;
    }

    $request = request_create_one();
    $request->setGet([
        'age' => 'happy',
    ]);
    try {
        DTOFromRequestWithValidateTypeError::fromRequest($request, 'get');
    } catch (DTOAssignPropertyException $e) {
        expect(array_keys($e->getErrors()))->toBe(['age'])
            ->and($e->first())->toBe('assign property error: age');
    }
});
