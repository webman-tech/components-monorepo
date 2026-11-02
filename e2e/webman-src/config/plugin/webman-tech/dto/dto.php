<?php

use app\support\PrefixCipher;

/**
 * e2e：dto 包的字段加解密配置
 *
 * 包内 ConfigHelper 读取 plugin.webman-tech.dto.dto.*，
 * 即本文件（config/plugin/webman-tech/dto/dto.php）的顶层键
 */
return [
    'data_encrypter' => PrefixCipher::class,
    'data_decrypter' => PrefixCipher::class,
];
