<?php

namespace WebmanTech\DTO;

use Webman\Http\Response;
use WebmanTech\DTO\Helper\ConfigHelper;

class BaseResponseDTO extends BaseDTO
{
    private string|\Closure|null $toResponseFormat = null;

    /**
     * 转为 Response
     * @return Response
     */
    public function toResponse(): Response
    {
        if ($this->toResponseFormat === null) {
            $this->toResponseFormat = ConfigHelper::get('dto.to_response_format', 'json');
        }

        $data = $this->toArray();

        if ($this->toResponseFormat === 'json') {
            return json($data);
        }
        if ($this->toResponseFormat instanceof \Closure) {
            return call_user_func($this->toResponseFormat, $data);
        }

        throw new \InvalidArgumentException('toResponseFormat error: ' . $this->toResponseFormat);
    }
}
