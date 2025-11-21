<?php

use Psr\Log\LoggerInterface;
use WebmanTech\CommonUtils\Log;

test('channel', function () {
    $logger = Log::channel('test');
    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});
