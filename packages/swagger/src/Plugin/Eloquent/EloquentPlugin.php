<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Eloquent;

use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use WebmanTech\Swagger\Plugin\Eloquent\Translator\SchemaTranslator;

/**
 * 注册 Illuminate Eloquent Model Schema 扩展
 */
final class EloquentPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        if ($config !== []) {
            throw new \InvalidArgumentException('EloquentPlugin does not accept any settings');
        }
        $hooks->withAttributeFactory(static function (AttributeFactory $factory): void {
            $factory->getTranslators()->add(new SchemaTranslator());
        });
    }
}
