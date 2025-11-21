<?php

namespace WebmanTech\CommonUtils;

/**
 * 编码相关
 */
final class Encoding
{
    /**
     * 将值强制转为 utf8 编码，不能依赖此方法界定的 原字符 的编码（可能不准），但能确保返回的值一定是个 utf8 安全的
     * @template T
     * @param T $value
     * @return T
     */
    public static function toUTF8(mixed $value): mixed
    {
        if (is_string($value)) {
            $detectedEncoding = mb_detect_encoding($value, ['UTF-8', 'GB2312', 'GBK', 'ISO-8859-1'], true);
            if ($detectedEncoding === 'UTF-8') {
                return $value;
            }
            if ($detectedEncoding) {
                $newValue = mb_convert_encoding($value, 'UTF-8', $detectedEncoding);
                if (is_string($newValue)) {
                    /** @phpstan-ignore return.type */
                    return $newValue;
                }
            }
            // 保底去掉非 ASCII 的字符
            /** @phpstan-ignore return.type */
            return (string)preg_replace('/[^\x00-\x7F]/', '-', $value);
        } elseif (is_array($value)) {
            $newValue = [];
            foreach ($value as $k => $v) {
                $newValue[self::toUTF8($k)] = self::toUTF8($v);
            }
            /** @phpstan-ignore return.type */
            return $newValue;
        }

        return $value;
    }
}
