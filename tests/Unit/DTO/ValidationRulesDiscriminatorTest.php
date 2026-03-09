<?php

use WebmanTech\DTO\Attributes\ValidationRulesDiscriminator;
use WebmanTech\DTO\BaseDTO;

// 定义测试用的 DTO 类
class ValidationRulesDiscriminatorTestNormalDTO extends BaseDTO
{
    public string $customerName;
    public ?string $pickupTime = null;
}

class ValidationRulesDiscriminatorTestExpressDTO extends BaseDTO
{
    public string $expressCompany;
    public string $trackingNumber;
    public string $receiverAddress;
    public ?float $shippingFee = null;
}

// 定义测试用的枚举
enum ValidationRulesDiscriminatorTestOrderType: string
{
    case Normal = 'normal';
    case Express = 'express';
}

beforeEach(function () {
    $this->discriminator = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]
    );
});

test('construct with string keys', function () {
    $discriminator = new ValidationRulesDiscriminator(
        property: 'order_type',
        mapping: [
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]
    );

    expect($discriminator->property)->toBe('order_type')
        ->and($discriminator->mapping)->toBe([
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]);
});

test('construct with integer keys', function () {
    $discriminator = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            1 => ValidationRulesDiscriminatorTestNormalDTO::class,
            2 => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]
    );

    expect($discriminator->property)->toBe('type')
        ->and($discriminator->mapping)->toBe([
            1 => ValidationRulesDiscriminatorTestNormalDTO::class,
            2 => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]);
});

test('fromData with array', function () {
    $discriminator = ValidationRulesDiscriminator::fromData([
        'property' => 'type',
        'mapping' => [
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ],
    ]);

    expect($discriminator)->toBeInstanceOf(ValidationRulesDiscriminator::class)
        ->and($discriminator->property)->toBe('type')
        ->and($discriminator->mapping)->toBe([
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]);
});

test('fromData with self instance', function () {
    $original = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
        ]
    );

    $result = ValidationRulesDiscriminator::fromData($original);

    expect($result)->toBe($original);
});

test('toArray', function () {
    $array = $this->discriminator->toArray();

    expect($array)->toBe([
        'property' => 'type',
        'mapping' => [
            'normal' => ValidationRulesDiscriminatorTestNormalDTO::class,
            'express' => ValidationRulesDiscriminatorTestExpressDTO::class,
        ],
    ]);
});

test('makeValueFromContext with normal type', function () {
    $value = [
        'customerName' => '张三',
        'pickupTime' => '2024-03-06 12:00',
    ];
    $context = ['type' => 'normal'];

    $result = $this->discriminator->makeValueFromContext($value, $context);

    expect($result)->toBeInstanceOf(ValidationRulesDiscriminatorTestNormalDTO::class)
        ->and($result->customerName)->toBe('张三')
        ->and($result->pickupTime)->toBe('2024-03-06 12:00');
});

test('makeValueFromContext with express type', function () {
    $value = [
        'expressCompany' => '顺丰',
        'trackingNumber' => 'SF1234567890',
        'receiverAddress' => '北京市朝阳区xxx',
        'shippingFee' => 15.0,
    ];
    $context = ['type' => 'express'];

    $result = $this->discriminator->makeValueFromContext($value, $context);

    expect($result)->toBeInstanceOf(ValidationRulesDiscriminatorTestExpressDTO::class)
        ->and($result->expressCompany)->toBe('顺丰')
        ->and($result->trackingNumber)->toBe('SF1234567890')
        ->and($result->receiverAddress)->toBe('北京市朝阳区xxx')
        ->and($result->shippingFee)->toBe(15.0);
});

test('makeValueFromContext with integer discriminator value', function () {
    $discriminator = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            1 => ValidationRulesDiscriminatorTestNormalDTO::class,
            2 => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]
    );

    $value = ['customerName' => '张三'];
    $context = ['type' => 1]; // 使用整数

    $result = $discriminator->makeValueFromContext($value, $context);

    expect($result)->toBeInstanceOf(ValidationRulesDiscriminatorTestNormalDTO::class)
        ->and($result->customerName)->toBe('张三');
});

test('makeValueFromContext with integer discriminator key', function () {
    $discriminator = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            1 => ValidationRulesDiscriminatorTestNormalDTO::class,
            2 => ValidationRulesDiscriminatorTestExpressDTO::class,
        ]
    );

    $value = ['customerName' => '张三'];
    $context = ['type' => 1];

    $result = $discriminator->makeValueFromContext($value, $context);

    expect($result)->toBeInstanceOf(ValidationRulesDiscriminatorTestNormalDTO::class)
        ->and($result->customerName)->toBe('张三');
});

test('makeValueFromContext with enum discriminator value in context', function () {
    $value = ['customerName' => '张三'];
    // context 中的 type 值是枚举实例，这是合法的
    $context = ['type' => ValidationRulesDiscriminatorTestOrderType::Normal];

    $result = $this->discriminator->makeValueFromContext($value, $context);

    expect($result)->toBeInstanceOf(ValidationRulesDiscriminatorTestNormalDTO::class)
        ->and($result->customerName)->toBe('张三');
});

test('makeValueFromContext with nullable and empty array', function () {
    $value = [];
    $context = ['type' => 'normal'];

    $result = $this->discriminator->makeValueFromContext($value, $context, nullable: true);

    expect($result)->toBeNull();
});

test('makeValueFromContext throws exception when discriminator property not in context', function () {
    $value = ['customerName' => '张三'];
    $context = ['other' => 'value'];

    $this->discriminator->makeValueFromContext($value, $context);
})->throws(InvalidArgumentException::class, 'Discriminator property "type" not found in context');

test('makeValueFromContext throws exception when discriminator value not in mapping', function () {
    $value = ['customerName' => '张三'];
    $context = ['type' => 'invalid'];

    $this->discriminator->makeValueFromContext($value, $context);
})->throws(InvalidArgumentException::class, 'Discriminator value "invalid" not found in mapping');

test('makeValueFromContext throws exception when DTO class does not exist', function () {
    $discriminator = new ValidationRulesDiscriminator(
        property: 'type',
        mapping: [
            'normal' => 'NonExistentClass',
        ]
    );

    $value = ['customerName' => '张三'];
    $context = ['type' => 'normal'];

    $discriminator->makeValueFromContext($value, $context);
})->throws(InvalidArgumentException::class, 'Discriminator mapping class "NonExistentClass" does not exist');

test('makeValueFromContext throws exception when value is not array', function () {
    $value = 'not an array';
    $context = ['type' => 'normal'];

    $this->discriminator->makeValueFromContext($value, $context);
})->throws(InvalidArgumentException::class, 'Discriminator value must be array, got string');
