<?php

namespace app\dto;

use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseRequestDTO;
use OpenApi\Spec as OA;

class CreateUserDTO extends BaseRequestDTO
{
    #[OA\Property(property: 'name', schema: new OA\Schema(description: '用户名'))]
    #[ValidationRules(required: true, string: true, minLength: 2, maxLength: 20)]
    public string $name;

    #[OA\Property(property: 'age', schema: new OA\Schema(description: '年龄'))]
    #[ValidationRules(required: true, integer: true, min: 1, max: 150)]
    public int $age;

    #[OA\Property(property: 'email', schema: new OA\Schema(description: '邮箱（可选）'))]
    #[ValidationRules(string: true)]
    public ?string $email = null;
}
