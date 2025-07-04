<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(description: '颜色枚举')]
enum EnumColor: string
{
    case R = 'red';
    case G = 'green';
    case B = 'blue';

    public function description(): string
    {
        return match ($this) {
            self::R => '红色',
            self::G => '绿色',
            self::B => '蓝色',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::R => '红色1',
            self::G => '绿色1',
            self::B => '蓝色1',
        };
    }
}
