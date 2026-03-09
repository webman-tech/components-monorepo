<?php

namespace Tests\Fixtures\DTO;

use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;

/**
 * 创建订单请求
 */
class CreateOrderRequest extends BaseDTO
{
    // discriminator 字段
    #[ValidationRules(required: true, in: ['normal', 'express'])]
    public string $type;
    
    // 根据 type 的值决定 order_data 的具体类型
    #[ValidationRules(
        required: true,
        discriminator: [
            'property' => 'type',
            'mapping' => [
                'normal' => NormalOrderDataDTO::class,
                'express' => ExpressOrderDataDTO::class,
            ],
        ]
    )]
    public NormalOrderDataDTO|ExpressOrderDataDTO $order_data;
    
    #[ValidationRules(required: true)]
    public string $userId;
}

/**
 * 普通订单数据
 */
class NormalOrderDataDTO extends BaseDTO
{
    #[ValidationRules(required: true)]
    public string $customerName;
    
    #[ValidationRules]
    public ?string $pickupTime = null;
}

/**
 * 快递订单数据
 */
class ExpressOrderDataDTO extends BaseDTO
{
    #[ValidationRules(required: true)]
    public string $expressCompany;
    
    #[ValidationRules(required: true)]
    public string $trackingNumber;
    
    #[ValidationRules(required: true)]
    public string $receiverAddress;
    
    #[ValidationRules]
    public ?float $shippingFee = null;
}
