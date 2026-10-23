<?php

namespace WebmanTech\DTO;

/**
 * 字段加密算法
 *
 * 由业务方实现，通过 dto.data_encrypter 配置注入给 EncryptFields
 */
interface DataEncrypterInterface
{
    /**
     * @param string $plaintext 明文
     * @param string $fieldName 字段名，便于按字段选用密钥或算法
     */
    public function encrypt(string $plaintext, string $fieldName): string;
}
