<?php

declare(strict_types=1);

namespace Tests\Fixtures\Swagger\Spec\lower_case;

use WebmanTech\DTO\BaseDTO;

final class NamedDTO extends BaseDTO
{
    public string $name;
}
