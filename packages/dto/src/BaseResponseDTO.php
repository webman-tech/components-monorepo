<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Integrations\Response;

class BaseResponseDTO extends BaseDTO
{
    private string|\Closure|null $toResponseFormat = null;

    /**
     * 转为 Response
     * @return mixed
     */
    public function toResponse()
    {
        if ($this->toResponseFormat === null) {
            $this->toResponseFormat = ConfigHelper::get('dto.to_response_format', 'json');
        }

        $data = $this->toArray();

        if ($this->toResponseFormat === 'json') {
            return Response::create()->json($data);
        }
        if ($this->toResponseFormat instanceof \Closure) {
            return call_user_func($this->toResponseFormat, $data);
        }

        throw new \InvalidArgumentException('toResponseFormat error');
    }
}
