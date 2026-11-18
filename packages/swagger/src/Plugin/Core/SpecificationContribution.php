<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\Plugin\Core;

use OpenApi\Spec as OA;
use OpenApi\Specification;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 在 Resolver 前声明 XSchema 依赖，并补充空文档默认信息
 */
final readonly class SpecificationContribution
{
    public function __construct(
        private ?string $responseLayoutClass = null,
        private bool $documentDefaults = true,
    )
    {
    }

    public function __invoke(Specification $spec): void
    {
        $spec->getWalker()->visit(OA\Operation::class, static function (OA\Operation $operation) use ($spec): void {
            foreach ([
                SchemaConstants::X_SCHEMA_REQUEST,
                SchemaConstants::X_SCHEMA_RESPONSE,
                SchemaConstants::X_RESPONSE_LAYOUT,
            ] as $key) {
                $spec->add(...SchemaDependency::from($operation->x[$key] ?? null));
            }
        });
        $spec->add(...SchemaDependency::from($this->responseLayoutClass));
        if ($this->documentDefaults) {
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
    }
}
