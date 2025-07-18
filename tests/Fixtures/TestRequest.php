<?php

namespace Tests\Fixtures;

use Webman\Http\Request;

class TestRequest extends Request
{
    public function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}
