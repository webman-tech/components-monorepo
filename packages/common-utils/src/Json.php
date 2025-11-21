<?php

namespace WebmanTech\CommonUtils;

use DateTimeInterface;
use Iterator;
use JsonException;
use JsonSerializable;
use SimpleXMLElement;
use stdClass;
use function is_array;
use function is_object;
use function is_resource;

/**
 * json 相关
 */
class Json
{
    /**
     * encode UTF-8
     *
     * @param mixed $value
     * @param int|null $options {@see http://www.php.net/manual/en/function.json-encode.php}
     * @param int<1, max>|null $depth
     * @param bool $throw 是否抛出异常
     * @return string
     * @throws JsonException
     */
    public static function encode(
        $value,
        ?int $options = null,
        ?int $depth = null,
        bool $throw = true
    ): string
    {
        try {
            $options ??= JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            $depth ??= 512;
            $expressions = [];
            $value = self::processData($value, $expressions, uniqid('', true));
            $json = json_encode($value, $options, $depth);
            if ($json === false) {
                $json = '';
            }
            if (($msg = json_last_error_msg()) !== 'No error') {
                if (json_last_error() === JSON_ERROR_INF_OR_NAN) {
                    // 在 json_encode 时发生 INF / NAN 错误时自动处理重试
                    if (($options | JSON_PARTIAL_OUTPUT_ON_ERROR) === $options) {
                        return $json;
                    }
                    return self::encode($value, $options | JSON_PARTIAL_OUTPUT_ON_ERROR, $depth, true);
                }
                if (json_last_error() === JSON_ERROR_UTF8) {
                    // 在 encode 时发生 UTF-8 错误时自动处理重试
                    $valueUTF8 = Encoding::toUTF8($value);
                    if ($valueUTF8 !== $value) {
                        return self::encode($valueUTF8, $options, $depth, true);
                    }
                }
                throw new JsonException($msg);
            }
            return strtr($json, $expressions);
        } catch (\Throwable $e) {
            if ($throw) {
                throw $e;
            }
            return ''; // 强制不抛出异常时返回空字符串
        }
    }

    /**
     * decode jsonData
     *
     * @param string $json
     * @param bool $asArray
     * @param int<1, max>|null $depth
     * @param int|null $options
     * @return mixed
     * @throws JsonException
     */
    public static function decode(
        string $json,
        bool   $asArray = true,
        ?int   $depth = null,
        ?int   $options = null,
    ): mixed
    {
        if ($json === '') {
            return null;
        }
        $options ??= 0;
        $depth ??= 512;
        $result = json_decode($json, $asArray, $depth, $options);
        if (($msg = json_last_error_msg()) !== 'No error') {
            if (json_last_error() === JSON_ERROR_UTF8) {
                // 在 decode 时发生 UTF-8 错误时自动处理重试
                $jsonUTF8 = Encoding::toUTF8($json);
                if ($jsonUTF8 !== $json) {
                    return static::decode($jsonUTF8, $asArray, $depth, $options);
                }
            }
            throw new JsonException($msg);
        }
        return $result;
    }

    /**
     * Pre-processes the data before sending it to `json_encode()`.
     *
     * @param mixed $data The data to be processed.
     * @param array $expressions collection of JavaScript expressions
     * @param string $expPrefix a prefix internally used to handle JS expressions
     *
     * @return mixed The processed data.
     */
    private static function processData($data, &$expressions, $expPrefix)
    {
        if (is_object($data)) {
            if ($data instanceof Json\Expression) {
                $token = "!{[$expPrefix=" . count($expressions) . ']}!';
                $expressions['"' . $token . '"'] = $data->expression;

                return $token;
            }

            if ($data instanceof JsonSerializable) {
                return self::processData($data->jsonSerialize(), $expressions, $expPrefix);
            }

            if ($data instanceof DateTimeInterface) {
                return self::processData((array)$data, $expressions, $expPrefix);
            }

            if ($data instanceof SimpleXMLElement) {
                $data = (array)$data;
            } elseif ($data instanceof Iterator) {
                $result = [];
                foreach ($data as $name => $value) {
                    /** @phpstan-ignore-next-line */
                    $result[$name] = $value;
                }
                $data = $result;
            }
            if ($data === []) {
                return new stdClass();
            }
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value) || is_resource($value)) {
                    $data[$key] = self::processData($value, $expressions, $expPrefix);
                }
            }
        } elseif (is_resource($data)) {
            $data = '__RESOURCE__';
        }

        return $data;
    }
}
