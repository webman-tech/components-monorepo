<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\DTO;

use WebmanTech\Swagger\Helper\ArrayHelper;

/**
 * 按构造参数读取组件配置，使基础文档功能无需依赖 DTO 包
 *
 * @internal
 */
abstract class BaseConfigDTO
{
    public static function fromConfig(array|self $config = []): static
    {
        if ($config instanceof static) {
            return $config;
        }
        $data = ArrayHelper::merge(static::getAppConfig(), is_array($config) ? $config : get_object_vars($config));
        $parameters = (new \ReflectionMethod(static::class, '__construct'))->getParameters();
        $names = array_map(static fn (\ReflectionParameter $parameter) => $parameter->getName(), $parameters);

        return (new \ReflectionClass(static::class))->newInstanceArgs(array_intersect_key($data, array_flip($names)));
    }

    protected static function getAppConfig(): array
    {
        return [];
    }
}
