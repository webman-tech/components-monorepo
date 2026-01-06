<?php

namespace Tests\Fixtures\Swagger\ExampleDTO;

use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;

final class UserCreateForm extends BaseRequestDTO
{
    /**
     * 姓名
     * @example 张三
     */
    public string $name;

    /**
     * @example 18
     */
    public int $age;

    /**
     * @example false
     */
    public bool $is_admin;

    /**
     * @var UserCreateFormAddressItem[]
     */
    public array|null $address = null;

    /**
     * 身份证
     */
    public UserCreateFormIdCard|null $id_card = null;

    /**
     * @var array<string, UserCreateFormAddressItem[]>
     */
    public array $namedAddressList = [];

    public function handle(): UserCreateFormResult
    {
        return new UserCreateFormResult(
            success: true,
        );
    }
}

final class UserCreateFormResult extends BaseResponseDTO
{
    public function __construct(
        /**
         * @example true
         */
        public bool $success,
    )
    {
    }
}

final class UserCreateFormAddressItem extends BaseDTO
{
    /**
     * 城市
     * @example 苏州
     */
    public string $city;

    /**
     * @example 18 栋
     */
    public string $address;
}

final class UserCreateFormIdCard extends BaseDTO
{
    /**
     * @example 1
     */
    public int $type;

    /**
     * @example 123456789
     */
    public string $number;
}
