<?php

namespace WebmanTech\DTO;

final class RouteDTO extends BaseDTO
{
    public function __construct(
        public string  $path,
        public string  $action,
        public ?string $controller = null,
        public string  $method = 'GET',
        public string  $desc = '',
        public ?string $name = null,
        public ?array  $middlewares = null,
    )
    {
    }
}
