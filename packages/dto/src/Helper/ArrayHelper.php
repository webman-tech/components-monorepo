<?php

namespace WebmanTech\DTO\Helper;

/**
 * @internal
 */
final class ArrayHelper
{
    /**
     * 合并数组
     * @param ...$arrays
     * @return array
     */
    public static function merge(...$arrays): array
    {
        $result = array_shift($arrays) ?: [];
        while (!empty($arrays)) {
            /** @var mixed $value */
            foreach (array_shift($arrays) as $key => $value) {
                if (is_int($key)) {
                    if (array_key_exists($key, $result)) {
                        if ($result[$key] !== $value) {
                            /** @var mixed */
                            $result[] = $value;
                        }
                    } else {
                        /** @var mixed */
                        $result[$key] = $value;
                    }
                } elseif (isset($result[$key]) && is_array($value) && is_array($result[$key])) {
                    $result[$key] = self::merge($result[$key], $value);
                } else {
                    /** @var mixed */
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * @template T
     * @param array<int|string, T> $array
     * @param T|null $default
     * @return T|null
     */
    public static function first(array $array, $default = null)
    {
        foreach ($array as $value) {
            return $value;
        }
        return $default;
    }
}
