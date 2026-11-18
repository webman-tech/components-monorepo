<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Dto;

use OpenApi\Augmenter;
use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use OpenApi\Utils\Pipeline;
use WebmanTech\Swagger\Plugin\Dto\Augmenter\CollectValidationRules;
use WebmanTech\Swagger\Plugin\Dto\Augmenter\SchemaAugmenter;
use WebmanTech\Swagger\Plugin\Dto\Augmenter\ValidationRulesDescription;
use WebmanTech\Swagger\Plugin\Dto\Translator\SchemaTranslator;
use WebmanTech\Swagger\Spec\ListPosition;

/**
 * 注册 DTO 属性、验证规则和 discriminator 扩展
 */
final class DtoPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        $options = new Options(...$config);
        $hooks->withAttributeFactory(static function (AttributeFactory $factory): void {
            $factory->getTranslators()->add(new SchemaTranslator());
        });
        $hooks->withAugmenters(static function (Pipeline $pipeline) use ($options): void {
            /*
             * 必填约束依赖官方 Properties 的推断结果；此位置早于 XSchema 展开和 Refs。
             */
            $pipeline->insert(new SchemaAugmenter(), ListPosition::after(Augmenter\Properties::class));
            if ($options->appendValidationRules) {
                $pipeline->insert(new CollectValidationRules(), ListPosition::after(SchemaAugmenter::class));
                $pipeline->insert(new ValidationRulesDescription(), ListPosition::after(Augmenter\Docblocks::class));
            }
        });
    }
}
