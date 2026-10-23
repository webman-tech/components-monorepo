<?php

namespace WebmanTech\DTO\Attributes;

use Attribute;

/**
 * 标记字段的返回值需要加密
 *
 * 由 toArray 默认处理器 EncryptFields 处理。配置 app.php 的 dto.data_encrypter 后，
 * 只有标记的字段会在 toArray 返回前加密
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DataEncrypt
{
}
