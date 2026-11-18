<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\WebmanHttp;

use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\AttributeFactory;
use WebmanTech\Swagger\Spec\Translator\UploadSchemaTranslator;

/**
 * 注册 WebmanHttp 上传文件类型，参与 binary Schema 和 multipart 推断
 */
final class WebmanHttpPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        if ($config !== []) {
            throw new \InvalidArgumentException('WebmanHttpPlugin does not accept any settings');
        }
        $hooks->withAttributeFactory(static function (AttributeFactory $factory): void {
            $translator = $factory->getTranslators()->get(UploadSchemaTranslator::class);
            if ($translator === null) {
                $factory->getTranslators()->add($translator = new UploadSchemaTranslator());
            }
            $translator->addType(\Webman\Http\UploadFile::class);
        });
    }
}
