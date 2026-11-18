<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\SymfonyHttp;

use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use WebmanTech\Swagger\Spec\Translator\UploadSchemaTranslator;

/**
 * 注册 SymfonyHttp 上传文件类型，参与 binary Schema 和 multipart 推断
 */
final class SymfonyHttpPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        if ($config !== []) {
            throw new \InvalidArgumentException('SymfonyHttpPlugin does not accept any settings');
        }
        $hooks->withAttributeFactory(static function (AttributeFactory $factory): void {
            $translator = $factory->getTranslators()->get(UploadSchemaTranslator::class);
            if ($translator === null) {
                $factory->getTranslators()->add($translator = new UploadSchemaTranslator());
            }
            $translator->addType(\Symfony\Component\HttpFoundation\File\UploadedFile::class);
        });
    }
}
