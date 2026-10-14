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
                // 处理 string[] 或 ClassName[] 或 ClassName[][] 等多维数组的解析
                $itemType = substr($type, 0, -2);
                // 递归处理多层 [] 的情况
                $nestedLevel = 1;
                while (str_ends_with($itemType, '[]')) {
                    $itemType = substr($itemType, 0, -2);
                    $nestedLevel++;
                }
                $parsedType = self::parseSingleType($itemType, $reflection->getDeclaringClass());
                if ($parsedType) {
                    // 根据嵌套层级构建 ValidationRules
                    while ($nestedLevel > 1) {
                        $parsedType = new ValidationRules(
                            arrayItem: $parsedType,
                        );
                        $nestedLevel--;
                    }
                    return $parsedType;
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
                    // 只按第一个逗号切分（value 本身可能是 array<int, Foo> 含内层逗号），
                    // 且不要求逗号后带空格，兼容 array<int,int> 这种紧凑写法
                    [, $valueType] = explode(',', $itemType, 2);
                    $valueType = trim($valueType);
                    // array<string, string|null> 检测和支持
                    $nullable = str_contains($valueType, '|null') || str_contains($valueType, 'null|');
                    if ($nullable) {
                        // 去掉 null
                        $valueType = str_replace(['|null', 'null|'], '', $valueType);
                    }
                    if (str_contains($valueType, '|')) {
                        // array<int|string, ClassName|xxx> 不支持多类型的情况
                        return null;
                    }
                    // 检测 array<string, ClassName[]> 或 array<string, ClassName[][]> 的情况（value 本身是数组）
                    $nestedLevel = 0;
                    $itemType = $valueType;
                    while (str_ends_with($itemType, '[]')) {
                        $itemType = substr($itemType, 0, -2);
                        $nestedLevel++;
                    }
                    if ($nestedLevel > 0) {
                        if ($type = self::parseSingleType($itemType, $reflection->getDeclaringClass())) {
                            // 构建嵌套的 ValidationRules
                            // 第一层：表示关联数组的值是数组类型
                            $arrayItem = new ValidationRules(
                                arrayItem: $type,
                            );
                            // 后续层：多维数组的额外层级
                            for ($i = 2; $i <= $nestedLevel; $i++) {
                                $arrayItem = new ValidationRules(
                                    arrayItem: $arrayItem,
                                );
                            }
                            return new ValidationRules(
                                nullable: $nullable === true ? true : null,
                                arrayItem: $arrayItem,
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
     * 类文件 use 语句映射的静态缓存（key: 文件路径）
     *
     * 原实现对每个 array 属性的类型解析都 file_get_contents 整个类文件并
     * 跑 2 次动态拼接的正则；缓存后每文件仅读盘一次，查找退化为 O(1)。
     *
     * @var array<string, array<string, string>>
     */
    private static array $useMapsByFile = [];

    /**
     * 提取类文件的 use 语句映射：末段类名 / 别名 => 完整类名
     * @return array<string, string>
     */
    private static function getUseMap(ReflectionClass $reflectionClass): array
    {
        $file = $reflectionClass->getFileName();
        if (!$file) {
            return [];
        }
        if (isset(self::$useMapsByFile[$file])) {
            return self::$useMapsByFile[$file];
        }

        $map = [];
        $content = file_get_contents($file);
        if ($content !== false) {
            preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $fqcn = ltrim($match[1], '\\');
                if (isset($match[2])) {
                    // use X\Y\Z as Alias：别名不参与末段匹配（与原逐条正则行为一致）
                    $map['@' . strtolower($match[2])] = $fqcn;
                    continue;
                }
                $segments = explode('\\', $fqcn);
                $map[strtolower(end($segments))] = $fqcn;
            }
        }

        return self::$useMapsByFile[$file] = $map;
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
            'mixed' => new ValidationRules(),
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
        // 非 \ 开头的，从 use 语句映射中查找（缓存，无需重复读文件）
        $map = self::getUseMap($reflectionClass);
        $needle = strtolower($singleType);
        if (isset($map[$needle])) {
            $fqcn = $map[$needle];
            /** @var class-string $fqcn use 语句声明的完整类名 */
            return $fqcn;
        }
        if (isset($map['@' . $needle])) {
            $fqcn = $map['@' . $needle];
            /** @var class-string $fqcn use as 别名声明的完整类名 */
            return $fqcn;
        }
        // use 里没有的，与当前类同 namespace
        $className = $reflectionClass->getNamespaceName() . '\\' . $singleType;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }
}
