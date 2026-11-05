<?php

namespace WebmanTech\Swagger\Controller;

use RuntimeException;
use Throwable;
use WebmanTech\CommonUtils\Local;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\View;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Helper\JsExpression;
use WebmanTech\Swagger\Spec\DocumentBuilder;

final class OpenapiController
{
    /**
     * Swagger UI 页面展示
     * @throws Throwable
     */
    public function swaggerUI(string $docRoute, ConfigSwaggerUiDTO|array $config = []): mixed
    {
        $config = ConfigSwaggerUiDTO::fromConfig($config);

        /*
         * 支持通过 request 传参来强制重新生成
         */
        if ($request = Request::getCurrent()) {
            $generate = (bool)($request->get('generate') ?? false);
            if ($generate) {
                $docRoute .= '?generate=1';
            }
        }

        $data = $config->data;
        $data['ui_config']['url'] = new JsExpression("window.location.pathname.replace(/\/+$/, '') + '/{$docRoute}'");
        $data['dto_generator_url'] ??= null;
        $data['dto_generator_config'] ??= [
            'defaultGenerationType' => 'form',
            'defaultNamespace' => 'app\\controller\\api\\form',
        ];

        $content = View::renderPHP(Local::combinePath($config->view_path, $config->view), $data);
        return Response::make()
            ->withHeaders(['Content-Type' => 'text/html; charset=utf-8'])
            ->withBody($content)
            ->getRaw();
    }

    /**
     * Openapi 文档
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = []): mixed
    {
        $config = ConfigOpenapiDocDTO::fromConfig($config);

        if ($config->max_execute_time) {
            set_time_limit($config->max_execute_time);
        }
        if ($config->max_memory_usage) {
            ini_set('memory_limit', $config->max_memory_usage . 'M');
        }

        $cache = $config->getCache();
        $cacheKey = $config->getCacheKey();

        $generate = false;
        if ($request = Request::getCurrent()) {
            $generate = (bool)($request->get('generate') ?? false);
        }

        $cached = $cache->get($cacheKey) ?? [null, null, null];
        /**
         * @var array{0: ?string, 1: ?string, 2: ?string} $cached
         */
        [$content, $contentType, $md5] = $cached;
        if ($md5 === null || $content === null || $contentType === null || $generate) {
            try {
                $result = (new DocumentBuilder($config))->build();
                [$content, $contentType] = $config->generateWithFormat($result->toArray());
                $md5 = md5($content);
            } catch (Throwable $e) {
                if ($config->generate_error_handler) {
                    ($config->generate_error_handler)($e);
                }
                throw $e;
            }

            $cache->set($cacheKey, [$content, $contentType, $md5]);
        }

        return Response::make()
            ->withHeaders(['Content-Type' => $contentType])
            ->withBody($content)
            ->getRaw();
    }

    /**
     * DTO 生成器页面
     */
    public function dtoGenerator(array|null $dtoGeneratorConfig = null): mixed
    {
        $path = ConfigHelper::getDtoGeneratorWebPath();
        if ($path === null) {
            throw new RuntimeException('DTO generator assets not found. Please install webman-tech/dto.');
        }
        $content = file_get_contents($path) ?: '';

        if ($dtoGeneratorConfig) {
            $dtoGeneratorConfig = json_encode($dtoGeneratorConfig);
            $prefix = <<<JS
<script>
window.__DTO_GENERATOR_CONFIG = {$dtoGeneratorConfig};
</script>
JS;
            $content = str_replace('</head>', $prefix . '</head>', $content);
        }

        return Response::make()
            ->withHeaders(['Content-Type' => 'text/html; charset=utf-8'])
            ->withBody($content)
            ->getRaw();
    }
}
