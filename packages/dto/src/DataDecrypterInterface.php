<?php

namespace WebmanTech\DTO;

/**
 * 字段解密算法
 *
 * 由业务方实现，通过 dto.data_decrypter 配置注入给 DecryptFields
 */
interface DataDecrypterInterface
{
    /**
     * @param string $ciphertext 密文
     * @param string $fieldName 字段名，便于按字段选用密钥或算法
     */
    public function decrypt(string $ciphertext, string $fieldName): string;
}
