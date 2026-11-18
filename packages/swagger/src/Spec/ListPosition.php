<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use Closure;
use OpenApi\OpenApiException;

/**
 * 为官方 TypedList 的 insert 提供相邻节点定位，保持组内执行顺序
 *
 * @internal
 */
final class ListPosition
{
    /**
     * @param class-string $class
     * @return Closure(list<object>): int
     */
    public static function after(string $class): Closure
    {
        return static function (array $items) use ($class): int {
            foreach ($items as $index => $item) {
                if ($item instanceof $class) {
                    return $index + 1;
                }
            }

            throw new OpenApiException('Missing extension point: ' . $class);
        };
    }
}
