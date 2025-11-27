<?php

namespace WebmanTech\DTO;

use WebmanTech\CommonUtils\Response;
use WebmanTech\DTO\Helper\ConfigHelper;

class BaseResponseDTO extends BaseDTO
{
    private array $responseHeaders = [];

    public function withResponseHeaders(array $headers): static
    {
        $this->responseHeaders = $headers;
        return $this;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    private int $responseStatus = 200;

    public function withResponseStatus(int $status): static
    {
        $this->responseStatus = $status;
        return $this;
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    private ?string $responseStatusText = null;

    public function withResponseStatusText(?string $text): static
    {
        $this->responseStatusText = $text;
        return $this;
    }

    public function getResponseStatusText(): ?string
    {
        return $this->responseStatusText;
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

        if ($this->toResponseFormat === 'json') {
            $data = $this->toArray();
            if ($data === []) {
                $data = new \stdClass();
            }
            return Response::make()
                ->withStatus($this->getResponseStatus(), $this->getResponseStatusText())
                ->withHeaders(array_merge([
                    'Content-Type' => 'application/json',
                ], $this->getResponseHeaders()))
                ->withBody(json_encode($data) ?: '')
                ->toRaw();
        }

        if ($this->toResponseFormat instanceof \Closure) {
            return ($this->toResponseFormat)($this);
        }

        throw new \InvalidArgumentException('toResponseFormat error');
    }
}
