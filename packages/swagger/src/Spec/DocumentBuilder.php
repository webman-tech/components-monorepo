<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Spec;

use OpenApi\Augmenter;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Builder\Result;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Utils\Pipeline;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

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
        $builder->getAttributeFactory()->getTranslators()->add(new SchemaTranslator($this->config));
        if ($this->config->schema_name_format_use_classname !== null) {
            $builder->setComponentNamer(new ComponentNamer($this->config->schema_name_format_use_classname));
        }

        $configureSpecification = function (Specification $spec) use ($documentDefaults): void {
            /*
             * 将 XSchema 类名表达为内部引用，由官方 Resolver 使用当前 Assembler 收集。
             * 临时依赖在展开阶段移除，避免影响后续 Cleanup。
             */
            $spec->getWalker()->visit(OA\Operation::class, static function (OA\Operation $operation) use ($spec): void {
                foreach ([
                    SchemaConstants::X_SCHEMA_REQUEST,
                    SchemaConstants::X_SCHEMA_RESPONSE,
                    SchemaConstants::X_RESPONSE_LAYOUT,
                ] as $key) {
                    $spec->add(...SchemaDependency::from($operation->x[$key] ?? null));
                }
            });
            $spec->add(...SchemaDependency::from($this->config->response_layout_class));
            if ($documentDefaults) {
                $spec->info ??= new OA\Info(title: 'App OpenAPI', version: '0.1');
                if ($spec->operations === [] && $spec->pathItems === []) {
                    $spec->add(new OA\Operation\Get(
                        path: '/__example',
                        description: 'Will be deleted automatically when adding a new api interface',
                        summary: 'Zero configuration example',
                        responses: [new OA\Response(response: 200, description: 'The data')],
                    ));
                }
            }
        };
        $builder->withAugmenters(function (Pipeline $pipeline) use ($routeMetadata): void {
            /*
             * DTO 展开和 XSchema 转换会新增组件及引用，必须在 Refs 和 Cleanup 前执行。
             */
            $pipeline->insert(new DtoAugmenter(), Augmenter\Refs::class);
            $pipeline->insert(new XSchemaAugmenter($this->config), Augmenter\Refs::class);
            /*
             * 官方已处理类、方法及 PathItem 标签，且标签声明尚未被 Merge 合并。
             */
            $pipeline->insert(new OperationTags(), Augmenter\Types::class);
            $pipeline->get(Augmenter\Cleanup::class)?->setEnabled(
                $this->config->clean_unused_components_enable && !$routeMetadata,
            );
            $pipeline->remove(Augmenter\EnumDescriptions::class);
            $pipeline->insert(new FinalizeAugmenter($this->config, $routeMetadata), Augmenter\Tags::class);
        });
        $this->config->applyBuilder($builder);

        /*
         * 用户的 withSpecification 回调先执行，依赖收集才能覆盖程序化贡献的接口。
         */
        $builder->withSpecification($configureSpecification);

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
