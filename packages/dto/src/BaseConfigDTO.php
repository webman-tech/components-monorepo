<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Helper\ArrayHelper;

class BaseConfigDTO extends BaseDTO
{
    public static function fromConfig(array|BaseConfigDTO $config = [], bool $validate = true): static
    {
        if ($config instanceof static) {
            return $config;
        }
        $appConfig = static::getAppConfig();
        return static::fromData(ArrayHelper::merge($appConfig, $config), validate: $validate);
    }

    protected static function getAppConfig(): array
    {
        return [];
    }
}
