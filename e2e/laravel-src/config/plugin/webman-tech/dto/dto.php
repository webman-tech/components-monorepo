<?php

use App\Support\PrefixCipher;

/**
 * e2e：dto 包的字段加解密配置
 *
 * 包内 ConfigHelper 读取 plugin.webman-tech.dto.dto.*，laravel 的 config 目录支持嵌套，
 * 故本文件（config/plugin/webman-tech/dto/dto.php）顶层键即对应 webman 下的同名文件
 */
return [
    'data_encrypter' => PrefixCipher::class,
    'data_decrypter' => PrefixCipher::class,
];
