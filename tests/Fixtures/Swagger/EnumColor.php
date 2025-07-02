<?php

namespace Tests\Fixtures\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema]
enum EnumColor: string
{
    case Red = 'read';
    case Green = 'green';
    case Blue = 'blue';
}
