<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core;

use OpenApi\Augmenter;
use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use OpenApi\Utils\Pipeline;
use WebmanTech\Swagger\Plugin\Core\Augmenter\FinalizeAugmenter;
use WebmanTech\Swagger\Plugin\Core\Augmenter\OperationTags;
use WebmanTech\Swagger\Plugin\Core\Augmenter\XSchemaAugmenter;
use WebmanTech\Swagger\Spec\Translator\AutoSchemaTranslator;

/**
 * 注册通用 Spec 的自动属性、结构展开、标签和文档清理
 */
final class SpecCorePlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        $options = new Options(...$config);
        $hooks->withAttributeFactory(static function (AttributeFactory $factory) use ($options): void {
            $factory->getTranslators()->add(new AutoSchemaTranslator($options->autoLoadSchemaClasses));
        });
        $hooks->withAugmenters(static function (Pipeline $pipeline) use ($options): void {
            $pipeline->insert(new XSchemaAugmenter($options->responseLayoutClass, $options->responseLayoutDataCode), Augmenter\Refs::class);
            $pipeline->get(Augmenter\Cleanup::class)?->setEnabled(
                $options->cleanUnusedComponents && !$options->routeMetadata,
            );
            $pipeline->insert(new OperationTags(), Augmenter\Types::class);
            $pipeline->insert(new FinalizeAugmenter($options->sortComponents, $options->sortSchemas, $options->sortPaths), Augmenter\Tags::class);
        });
    }
}
