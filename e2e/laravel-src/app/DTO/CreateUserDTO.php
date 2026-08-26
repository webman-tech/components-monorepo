<?php

namespace App\DTO;

use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseRequestDTO;

class CreateUserDTO extends BaseRequestDTO
{
    #[ValidationRules(required: true, string: true, minLength: 2, maxLength: 20)]
    public string $name;

    #[ValidationRules(required: true, integer: true, min: 1, max: 150)]
    public int $age;

    #[ValidationRules(string: true)]
    public ?string $email = null;
}
