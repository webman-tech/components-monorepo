<?php

namespace WebmanTech\CommonUtils;

final class Constants
{
    public const ENV_LOCAL_IP = 'LOCAL_IP'; // 本机 ip

    public const RUNTIME_LARAVEL = 'laravel';
    public const RUNTIME_WEBMAN = 'webman';
    public const RUNTIME_WORKERMAN = 'workerman';
    public const RUNTIME_TEST = 'test';
    public const RUNTIME_CUSTOM = 'custom'; // 自定义环境
}
