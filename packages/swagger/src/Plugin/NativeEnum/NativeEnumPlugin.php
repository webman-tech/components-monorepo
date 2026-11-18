<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\NativeEnum;

use OpenApi\Augmenter;
use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use OpenApi\Utils\Pipeline;
use WebmanTech\Swagger\Plugin\NativeEnum\Augmenter\EnumDescriptions;
use WebmanTech\Swagger\Plugin\NativeEnum\Translator\SchemaTranslator;
use WebmanTech\Swagger\Spec\ListPosition;

/**
 * 注册 PHP Enum 的类型和值描述扩展
 */
final class NativeEnumPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        $options = new Options(...$config);
        $hooks->withAttributeFactory(static function (AttributeFactory $factory): void {
            $factory->getTranslators()->add(new SchemaTranslator());
        });
        $hooks->withAugmenters(static function (Pipeline $pipeline) use ($options): void {
            if (!$options->descriptionEnabled) {
                return;
            }

            $pipeline->remove(Augmenter\EnumDescriptions::class);
            $pipeline->insert(new EnumDescriptions($options->descriptionMethod), ListPosition::after(Augmenter\Docblocks::class));
        });
    }
}
