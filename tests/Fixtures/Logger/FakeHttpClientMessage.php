<?php

namespace Tests\Fixtures\Logger;

use WebmanTech\Logger\Helper\StringHelper;
use WebmanTech\Logger\Message\BaseHttpClientMessage;

/**
 * 用于测试的 HttpClient Message
 *
 * @internal 测试专用，模拟响应结构
 */
final class FakeHttpClientMessage extends BaseHttpClientMessage
{
    protected function getResponseStatus(mixed $response): int
    {
        return is_array($response) ? (int)($response['status'] ?? 0) : 0;
    }

    protected function getResponseContent(mixed $response, int $limitLength): string
    {
        if (!is_array($response)) {
            return '[Response Type error]';
        }

        $content = (string)($response['body'] ?? '');

        return StringHelper::limit($content, $limitLength);
    }
}
