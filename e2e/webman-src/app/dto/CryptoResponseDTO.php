<?php

namespace app\dto;

use WebmanTech\DTO\Attributes\DataEncrypt;
use WebmanTech\DTO\BaseDTO;

/**
 * 出参中标记 #[DataEncrypt] 的字段在 toArray() 时由 EncryptFields 加密
 *
 * 三类字段覆盖处理器的不同分支：
 * - token：普通字符串字段
 * - tags：字符串数组，逐项加密
 * - count：非字符串，原样透传
 */
class CryptoResponseDTO extends BaseDTO
{
    public string $result;

    #[DataEncrypt]
    public string $token;

    /**
     * @var string[]
     */
    #[DataEncrypt]
    public array $tags = [];

    #[DataEncrypt]
    public int $count = 0;
}
