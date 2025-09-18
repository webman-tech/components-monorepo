<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Helper\ConfigHelper;
use WebmanTech\DTO\Integrations\Response;

class BaseResponseDTO extends BaseDTO
{
    private array $responseHeaders = [];

    public function withResponseHeaders(array $headers): static
    {
        $this->responseHeaders = $headers;
        return $this;
    }

    private int $responseStatus = 200;

    public function withResponseStatus(int $status): static
    {
        $this->responseStatus = $status;
        return $this;
    }

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
            return Response::create()->json(
                data: $data === [] ? new \stdClass() : $data,
                status: $this->responseStatus,
                headers: $this->responseHeaders,
            );
        }

        if ($this->toResponseFormat instanceof \Closure) {
            return call_user_func($this->toResponseFormat, $data, $this->responseStatus, $this->responseHeaders);
        }

        throw new \InvalidArgumentException('toResponseFormat error');
    }
}
