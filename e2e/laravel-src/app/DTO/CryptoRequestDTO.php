<?php

namespace App\DTO;

use WebmanTech\DTO\Attributes\DataDecrypt;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseRequestDTO;

/**
 * 入参 secret 以密文传入
 *
 * #[DataDecrypt] 由默认处理器 DecryptFields 在清洗与验证之前执行，
 * 因此 email 规则校验的是解密后的明文（密文本身不是合法 email）
 */
class CryptoRequestDTO extends BaseRequestDTO
{
    #[DataDecrypt]
    #[ValidationRules(required: true, rules: 'email')]
    public string $secret;
}
