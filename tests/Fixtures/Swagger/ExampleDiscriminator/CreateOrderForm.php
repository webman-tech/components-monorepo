<?php

namespace Tests\Fixtures\Swagger\ExampleDiscriminator;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\Attributes\ValidationRulesDiscriminator;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;

#[Schema(schema: 'CreateOrderForm')]
final class CreateOrderForm extends BaseRequestDTO
{
    /**
     * 订单类型
     * @example normal
     */
    #[ValidationRules(required: true, in: ['normal', 'express'])]
    public string $type;

    /**
     * 用户ID
     * @example U123456
     */
    #[ValidationRules(required: true)]
    public string $userId;

    /**
     * 订单数据（根据 type 决定具体类型）
     */
    #[Property]
    #[ValidationRules(
        required: true,
        discriminator: new ValidationRulesDiscriminator(
            property: 'type',
            mapping: [
                'normal' => CreateOrderFormOrderDataNormal::class,
                'express' => CreateOrderFormOrderDataExpress::class,
            ]
        )
    )]
    public CreateOrderFormOrderDataNormal|CreateOrderFormOrderDataExpress $orderData;

    /**
     * 备注
     */
    public ?string $remark = null;

    public function handle(): CreateOrderResult
    {
        return new CreateOrderResult(
            success: true,
            orderId: 'ORD' . time(),
        );
    }
}

final class CreateOrderResult extends BaseResponseDTO
{
    public function __construct(
        /**
         * @example true
         */
        public bool $success,
        
        /**
         * @example ORD1234567890
         */
        public string $orderId,
    )
    {
    }
}

/**
 * 普通订单数据
 */
#[Schema]
final class CreateOrderFormOrderDataNormal extends BaseDTO
{
    /**
     * 客户姓名
     * @example 张三
     */
    #[ValidationRules(required: true)]
    public string $customerName;

    /**
     * 自提时间
     * @example 2024-03-06 12:00
     */
    public ?string $pickupTime = null;
}

/**
 * 快递订单数据
 */
#[Schema]
final class CreateOrderFormOrderDataExpress extends BaseDTO
{
    /**
     * 快递公司
     * @example 顺丰
     */
    #[ValidationRules(required: true)]
    public string $expressCompany;

    /**
     * 快递单号
     * @example SF1234567890
     */
    #[ValidationRules(required: true)]
    public string $trackingNumber;

    /**
     * 收货地址
     * @example 北京市朝阳区xxx
     */
    #[ValidationRules(required: true)]
    public string $receiverAddress;

    /**
     * 运费
     * @example 15.0
     */
    public ?float $shippingFee = null;
}
