<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\WebmanRoute;

use OpenApi\Augmenter;
use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Utils\Pipeline;
use WebmanTech\Swagger\Plugin\WebmanRoute\Augmenter\RouteMetadata;

/**
 * 注册 Webman 路由元数据处理；实际路由注册由应用启动流程完成
 */
final class WebmanRoutePlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        $options = new Options(...$config);
        $hooks->withAugmenters(static function (Pipeline $pipeline) use ($options): void {
            /*
             * 路由描述读取 Docblocks 和 DTO 验证规则描述增强后的结果。
             */
            $pipeline->insert(new RouteMetadata($options->preserveMetadata), Augmenter\Tags::class);
        });
    }
}
