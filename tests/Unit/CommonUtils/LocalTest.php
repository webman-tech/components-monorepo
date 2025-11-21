<?php

use WebmanTech\CommonUtils\Constants;
use WebmanTech\CommonUtils\EnvAttr;
use WebmanTech\CommonUtils\Local;
use function WebmanTech\CommonUtils\put_env;

beforeEach(function () {
    EnvAttr::reset();
});

test('get ip', function () {
    $ip = Local::getIp(true);
    expect($ip)->toBeString()
        ->and(count(explode('.', $ip)))->toBe(4) // 目前仅支持获取本地 ipv4
    ;
});

test('get ip by env', function () {
    put_env(Constants::ENV_LOCAL_IP, '1.1.1.1');

    $ip = Local::getIp(true);
    expect($ip)->toBe('1.1.1.1');
});
