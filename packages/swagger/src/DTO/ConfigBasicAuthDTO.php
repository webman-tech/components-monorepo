<?php

namespace WebmanTech\Swagger\DTO;

final class ConfigBasicAuthDTO extends BaseConfigDTO
{
    public function __construct(
        public bool   $enable = false,
        public string $username = '',
        public string $password = '',
        public string $realm = 'Swagger API Documentation',
    )
    {
    }
}
