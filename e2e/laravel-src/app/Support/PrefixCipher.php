<?php

namespace App\Support;

use RuntimeException;
use WebmanTech\DTO\DataDecrypterInterface;
use WebmanTech\DTO\DataEncrypterInterface;

/**
 * e2e 用的可逆“加密”实现：字段名 + 反转明文 + base64
 *
 * 不是真实加密算法，只用于验证字段级加解密链路。密文里带上字段名，
 * 便于断言 EncryptFields/DecryptFields 是按字段（而非整体）调用算法，
 * 同时让“另一字段的密文”无法被当作本字段密文使用。
 */
final class PrefixCipher implements DataEncrypterInterface, DataDecrypterInterface
{
    private const PREFIX = 'e2e:';

    public function encrypt(string $plaintext, string $fieldName): string
    {
        return self::PREFIX . $fieldName . ':' . base64_encode(strrev($plaintext));
    }

    public function decrypt(string $ciphertext, string $fieldName): string
    {
        $prefix = self::PREFIX . $fieldName . ':';
        if (!str_starts_with($ciphertext, $prefix)) {
            throw new RuntimeException("invalid ciphertext for field {$fieldName}");
        }

        $plaintext = base64_decode(substr($ciphertext, strlen($prefix)), true);
        if ($plaintext === false) {
            throw new RuntimeException("invalid ciphertext for field {$fieldName}");
        }

        return strrev($plaintext);
    }
}
