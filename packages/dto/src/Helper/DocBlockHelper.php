<?php

namespace WebmanTech\DTO\Helper;

use ReflectionClass;
use ReflectionProperty;
use WebmanTech\DTO\Attributes\ValidationRules;

/**
 * @internal
 */
final class DocBlockHelper
{
    /**
     * @return class-string|ValidationRules|null
     */
    public static function extractClassPropertyArrayItemType(ReflectionProperty $reflection): null|string|ValidationRules
    {
        $comment = $reflection->getDocComment();
        if (!$comment) {
            return null;
        }

        $comment = (string)str_replace("\r\n", "\n", $comment);
        $comment = (string)preg_replace('/\*\/[ \t]*$/', '', $comment); // strip '*/'
        preg_match('/@var\s+(?<type>[^\s]+)([ \t])?(?<description>.+)?$/im', $comment, $matches);

        if (!isset($matches['type'])) {
            return null;
        }

        $types = array_filter(explode('|', $matches['type']));

        foreach ($types as $type) {
            if (
                $type === 'array' // 纯 array 类型，无意义
                || str_starts_with($type, ' ') // 以空格开头的，是被 array<string, string> 这种切掉的，忽略掉
            ) {
                continue;
            }
            if (str_ends_with($type, '[]')) {
                // 处理 string[] 或 ClassName[] 类型的解析
                $itemType = substr($type, 0, -2);
                if ($type = self::parseSingleType($itemType, $reflection->getDeclaringClass())) {
                    return $type;
                }
            } elseif (str_starts_with($type, 'array<')) {
                // 处理 array<string, ClassName|xxx> 类型的解析
                // 重新以 comment 提取 array<> 中的内容，因为上方的正则会以空格切开，导致 $type 信息不全
                preg_match('/array<(.*)>/m', $comment, $matches);
                $itemType = $matches[1] ?? null;
                if (!$itemType) {
                    return null;
                }
                if (!str_contains($itemType, ',')) {
                    // array<int|string>  或 array<int> 的形式
                    if (str_contains($itemType, '|')) {
                        // array<int|string> 不支持多类型的情况
                        return null;
                    }
                    if ($type = self::parseSingleType($itemType, $reflection->getDeclaringClass())) {
                        // array<int>
                        return $type;
                    }
                } else {
                    [, $valueType] = explode(', ', $itemType);
                    $valueType = trim($valueType);
                    // array<string, string|null> 检测和支持
                    $nullable = str_contains($valueType, '|null') || str_contains($valueType, 'null|');
                    if ($nullable) {
                        // 去掉 null
                        $valueType = str_replace(['|null', '|null'], '', $valueType);
                    }
                    if (str_contains($valueType, '|')) {
                        // array<int|string, ClassName|xxx> 不支持多类型的情况
                        return null;
                    }
                    // 检测 array<string, ClassName[]> 的情况（value 本身是数组）
                    $isValueArray = str_ends_with($valueType, '[]');
                    if ($isValueArray) {
                        $itemType = substr($valueType, 0, -2);
                        if ($type = self::parseSingleType($itemType, $reflection->getDeclaringClass())) {
                            return new ValidationRules(
                                nullable: $nullable === true ? true : null,
                                arrayItem: new ValidationRules(
                                    arrayItem: $type,
                                ),
                                object: true,
                            );
                        }
                    }
                    if ($type = self::parseSingleType($valueType, $reflection->getDeclaringClass())) {
                        // array<int, ClassName>
                        return new ValidationRules(
                            nullable: $nullable === true ? true : null,
                            arrayItem: $type,
                            object: true,
                        );
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return null|class-string|ValidationRules
     */
    private static function parseSingleType(string $singleType, ReflectionClass $reflectionClass): null|string|ValidationRules
    {
        $singleType = match ($singleType) {
            'int' => new ValidationRules(integer: true),
            'string' => new ValidationRules(string: true),
            'float' => new ValidationRules(numeric: true),
            'bool' => new ValidationRules(boolean: true),
            default => $singleType,
        };
        if ($singleType instanceof ValidationRules) {
            return $singleType;
        }
        // 尝试获取完整的类名
        if (str_starts_with($singleType, '\\')) {
            // \ 开头的为全 namespace 的 class 名
            if (class_exists($singleType)) {
                // className
                return ltrim($singleType, '\\');
            }
            return null;
        }
        // 非 \ 开头的
        /** @phpstan-ignore-next-line */
        $content = file_get_contents($reflectionClass->getFileName());
        assert($content !== false);
        // 从 use 里提取
        preg_match('/use\s+((.*)\\\\' . $singleType . ');$/m', $content, $matches);
        if (isset($matches[1])) {
            /** @phpstan-ignore-next-line */
            return $matches[1];
        }
        // 从 use Xxx as Xxx 里提取
        preg_match('/use\s+(.*)\s+as\s+' . $singleType . ';$/m', $content, $matches);
        if (isset($matches[1])) {
            /** @phpstan-ignore-next-line */
            return $matches[1];
        }
        // use 里没有的，与当前类同 namespace
        $className = $reflectionClass->getNamespaceName() . '\\' . $singleType;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }
}
