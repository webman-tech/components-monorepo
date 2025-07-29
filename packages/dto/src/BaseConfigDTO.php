<?php

namespace WebmanTech\DTO;

use WebmanTech\DTO\Helper\ArrayHelper;

class BaseConfigDTO extends BaseDTO
{
    public static function fromConfig(array|BaseConfigDTO $config = [], bool $validate = false): static
    {
        // $validate 默认为 false 的原因：
        // 1. config 一般是服务端自己的配置，一般不需要校验，增加校验损耗性能
        // 2. validate 后只会返回验证后的数据，当 config 中有些配置是在 construct 中定义了，但是没有 public 属性，此时会丢失，参见 WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO
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
