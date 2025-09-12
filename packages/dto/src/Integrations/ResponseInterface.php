<?php

namespace WebmanTech\DTO\Integrations;

interface ResponseInterface
{
    /**
     * 将数据转为 json response
     */
    public function json(mixed $data): mixed;
}
