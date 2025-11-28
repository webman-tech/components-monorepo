<?php

namespace Tests\Fixtures\Logger;

use WebmanTech\Logger\Mode\BaseMode;

final class FakeMode extends BaseMode
{
    public function getHandler(string $channelName, string $level): array
    {
        if (!$this->checkHandlerUsefulForChannel($channelName)) {
            return [];
        }

        return [
            'class' => self::class,
            'constructor' => [
                'channel' => $channelName,
                'level' => $level,
                'config' => $this->commonConfig,
            ],
            'formatter' => $this->getFormatter(),
        ];
    }
}
