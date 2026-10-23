<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;

/**
 * 标记字段的值需要解密
 *
 * 由默认管线中的 DecryptFields 处理。配置 dto.data_decrypter 后，
 * 只有标记的字段会在清洗和验证前解密
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DataDecrypt
{
}
