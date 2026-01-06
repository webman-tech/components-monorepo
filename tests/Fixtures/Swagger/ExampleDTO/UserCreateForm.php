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

    /**
     * 简单字符串数组
     * @var string[]
     */
    public array $tags = [];

    /**
     * 整数数组
     * @var array<int>
     */
    public array $scores = [];

    /**
     * 浮点数数组
     * @var float[]
     */
    public array $prices = [];

    /**
     * 关联数组，值为字符串
     * @var array<string, string>
     */
    public array $metadata = [];

    /**
     * 关联数组，值为整数
     * @var array<string, int>
     */
    public array $counts = [];

    /**
     * 关联数组，值为对象
     * @var array<string, UserCreateFormSettings>
     */
    public array $settings = [];

    /**
     * 多维数组（数组的数组）
     * @var UserCreateFormAddressItem[][]
     */
    public array $matrixAddress = [];

    /**
     * 关联数组，值为多维数组
     * @var array<string, UserCreateFormAddressItem[][]>
     */
    public array $namedMatrixAddress = [];

    /**
     * 混合类型的关联数组
     * @var array<string, mixed>
     */
    public array $extra = [];

    /**
     * 可空的简单类型
     */
    public ?string $nickname = null;

    /**
     * 可空的浮点数
     */
    public ?float $height = null;

    /**
     * 带默认值的整数
     */
    public int $status = 1;

    /**
     * 带默认值的字符串
     */
    public string $role = 'user';

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

final class UserCreateFormSettings extends BaseDTO
{
    /**
     * 设置键
     * @example dark_mode
     */
    public string $key;

    /**
     * 设置值
     * @example true
     */
    public string $value;

    /**
     * 是否启用
     * @example true
     */
    public bool $enabled = true;
}
