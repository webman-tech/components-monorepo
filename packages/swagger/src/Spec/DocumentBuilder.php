<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Builder\Result;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\Plugin\Core\SpecCorePlugin;
use WebmanTech\Swagger\Plugin\Core\SpecificationContribution;
use WebmanTech\Swagger\Plugin\Dto\DtoPlugin;
use WebmanTech\Swagger\Plugin\Eloquent\EloquentPlugin;
use WebmanTech\Swagger\Plugin\NativeEnum\NativeEnumPlugin;
use WebmanTech\Swagger\Plugin\SymfonyHttp\SymfonyHttpPlugin;
use WebmanTech\Swagger\Plugin\WebmanHttp\WebmanHttpPlugin;
use WebmanTech\Swagger\Plugin\WebmanRoute\WebmanRoutePlugin;

/**
 * 通过 swagger-php 的公开扩展点配置 Spec 文档生成链路
 */
final readonly class DocumentBuilder
{
    public function __construct(private ConfigOpenapiDocDTO $config)
    {
    }

    /**
     * 创建 Builder；路由解析时关闭示例文档，并保留路由扩展元数据
     */
    public function create(bool $documentDefaults = true, bool $routeMetadata = false): Builder
    {
        $builder = (new Builder())
            ->setMode(Mode::SPEC)
            ->setVersion($this->config->openapi_version)
            ->addSource($this->config->getScanSources());
        if ($this->config->schema_name_format_use_classname !== null) {
            $builder->setComponentNamer(new ComponentNamer($this->config->schema_name_format_use_classname));
        }

        /*
         * 应用配置中的 null 表示沿用插件默认值；保留显式的 false、空字符串和空数组。
         */
        $plugins = [
            SpecCorePlugin::class => array_filter([
                'autoLoadSchemaClasses' => $this->config->auto_load_schema_classes,
                'cleanUnusedComponents' => $this->config->clean_unused_components_enable,
                'sortComponents' => $this->config->sort_components_enable,
                'sortSchemas' => $this->config->sort_components_sort_schemas,
                'sortPaths' => $this->config->sort_components_sort_paths,
                'responseLayoutClass' => $this->config->response_layout_class,
                'responseLayoutDataCode' => $this->config->response_layout_data_code,
                'routeMetadata' => $routeMetadata,
            ], static fn (mixed $value) => $value !== null),
            NativeEnumPlugin::class => array_filter([
                'descriptionEnabled' => $this->config->schema_enum_description_enable,
                'descriptionMethod' => $this->config->schema_enum_description_method,
            ], static fn (mixed $value) => $value !== null),
        ];
        if (class_exists(\Illuminate\Database\Eloquent\Model::class) && $this->config->expand_eloquent_model_enable) {
            $plugins[] = EloquentPlugin::class;
        }
        if (class_exists(\WebmanTech\DTO\BaseDTO::class)) {
            $plugins[DtoPlugin::class] = [
                'appendValidationRules' => $this->config->append_dto_validation_rules_in_description,
            ];
        }
        if (class_exists(\Webman\Route::class)) {
            $plugins[WebmanRoutePlugin::class] = ['preserveMetadata' => $routeMetadata];
        }
        if (class_exists(\Webman\Http\UploadFile::class)) {
            $plugins[] = WebmanHttpPlugin::class;
        }
        if (class_exists(\Symfony\Component\HttpFoundation\File\UploadedFile::class)) {
            $plugins[] = SymfonyHttpPlugin::class;
        }
        $builder->withPlugins($plugins);
        $this->config->applyBuilder($builder);

        /*
         * 应用可覆盖插件配置；依赖贡献最后注册，以覆盖程序化新增的接口。
         */
        $builder->withSpecification(new SpecificationContribution(
            $this->config->response_layout_class,
            $documentDefaults,
        ));

        return $builder;
    }

    /**
     * 生成文档，并按配置将校验失败转换为异常
     */
    public function build(): Result
    {
        $result = $this->create()->build();
        if ($this->config->openapi_validate && !$result->isValid()) {
            /*
             * errors() 在 swagger-php 8.0 移除，升级时改为通过 setLogger 收集
             */
            $errors = $result->errors();
            throw new \RuntimeException(
                'OpenAPI validation failed' . ($errors !== [] ? ': ' . implode('; ', $errors) : ''),
            );
        }

        return $result;
    }
}
