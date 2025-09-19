<?php

namespace WebmanTech\DTO\Integrations;

use WebmanTech\DTO\BaseResponseDTO;

interface ResponseInterface
{
    /**
     * 将数据转为 json response
     */
    public function json(mixed $data, BaseResponseDTO $responseDTO): mixed;
}
