<?php

namespace WebmanTech\DTO\Helper;

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
            if ($type === 'array') {
                continue;
            }
            if (!str_ends_with($type, '[]')) {
                continue;
            }
            // 仅处理 string[] 或 ClassName[] 类型的解析
            $itemType = substr($type, 0, -2);
            $itemType = match ($itemType) {
                'int' => new ValidationRules(integer: true),
                'string' => new ValidationRules(string: true),
                'float' => new ValidationRules(numeric: true),
                'bool' => new ValidationRules(boolean: true),
                default => $itemType,
            };
            if ($itemType instanceof ValidationRules) {
                return $itemType;
            }
            // 尝试获取完整的类名
            if (str_starts_with($itemType, '\\')) {
                // \ 开头的为全 namespace 的 class 名
                if (class_exists($itemType)) {
                    // className
                    return ltrim($itemType, '\\');
                }
                return null;
            }
            // 非 \ 开头的
            /** @phpstan-ignore-next-line */
            $content = file_get_contents($reflection->getDeclaringClass()->getFileName());
            assert($content !== false);
            // 从 use 里提取
            preg_match('/use\s+((.*)\\\\' . $itemType . ');$/m', $content, $matches);
            if (isset($matches[1])) {
                /** @phpstan-ignore-next-line */
                return $matches[1];
            }
            // use 里没有的，与当前类同 namespace
            $className = $reflection->getDeclaringClass()->getNamespaceName() . '\\' . $itemType;
            if (class_exists($className)) {
                return $className;
            }
        }

        return null;
    }
}
