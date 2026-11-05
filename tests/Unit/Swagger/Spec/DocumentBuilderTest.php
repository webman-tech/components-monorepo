<?php

declare(strict_types=1);

use OpenApi\Builder;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use OpenApi\Utils\Pipeline;
use Tests\Fixtures\Swagger\TestFactory;
use Tests\Fixtures\Swagger\Spec\Envelope;
use Tests\Fixtures\Swagger\Spec\ZeroAndDefaultForm;
use Tests\Fixtures\Swagger\ExampleDTO\UserController;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Spec\DocumentBuilder;

require_once __DIR__ . '/../../../Fixtures/Swagger/Spec/Integration.php';
require_once __DIR__ . '/../../../Fixtures/Swagger/Spec/HandlerResponses.php';
require_once __DIR__ . '/../../../Fixtures/Swagger/Spec/CustomModel.php';
require_once __DIR__ . '/../../../Fixtures/Swagger/Spec/SchemaDependencies.php';
require_once __DIR__ . '/../../../Fixtures/Swagger/Spec/PropertyDocumentation.php';

test('uses only the spec pipeline and generates a default document', function () {
    $result = TestFactory::build();
    expect($result->openApi())->toBeNull()
        ->and($result->specification())->toBeInstanceOf(Specification::class)
        ->and($result->isValid())->toBeTrue()
        ->and($result->toArray()['paths'])->toHaveKey('/__example');
});

test('supports programmatic contributions and custom augmenters without scanned sources', function () {
    $result = TestFactory::build(config: [
        'builder_modify' => function (Builder $builder): void {
            $builder->withSpecification(fn (Specification $spec) => $spec->add(new OA\Operation\Get(path: '/programmatic')));
            $builder->withAugmenters(fn (Pipeline $pipeline) => $pipeline->add(function (Specification $spec): Specification {
                $spec->operations[0]->summary = 'Custom augmenter';

                return $spec;
            }));
        },
    ]);
    expect($result->toArray()['paths']['/programmatic']['get']['summary'])->toBe('Custom augmenter')
        ->and($result->toArray()['paths'])->not->toHaveKey('/__example');
});

test('resolves DTOs and XSchema handler returns from a single reflected controller source', function () {
    $config = new ConfigOpenapiDocDTO(scan_path: [new ReflectionClass(UserController::class)]);
    $doc = (new DocumentBuilder($config))->build()->toArray();
    expect($doc['paths']['/users']['post']['requestBody']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/UserCreateForm')
        ->and($doc['components']['schemas'])->toHaveKeys(['UserCreateForm', 'UserCreateFormResult', 'UserCreateFormIdCard']);
});

test('infers schema and public properties without explicit spec attributes', function () {
    $doc = TestFactory::build(['Spec/PlainDtoClass.php'])->toArray();
    expect(array_keys($doc['components']['schemas']['PlainDtoClass']['properties']))->toBe(['name', 'age']);
});

test('resolves XSchema dependencies through the configured resolver without scanning DTOs', function (bool $scanController) {
    $resolver = new class extends \OpenApi\Resolver\Reflection {
        public array $classes = [];

        public function resolveKey(\OpenApi\Specification\ComponentKey $key, \OpenApi\Assembler $assembler): bool
        {
            $this->classes[] = $key->class;

            return parent::resolveKey($key, $assembler);
        }
    };
    $builder = (new DocumentBuilder(new ConfigOpenapiDocDTO(
        scan_path: $scanController ? [new ReflectionClass(\Tests\Fixtures\Swagger\Spec\DependencyController::class)] : [],
        response_layout_class: \Tests\Fixtures\Swagger\Spec\DependencyLayout::class,
        clean_unused_components_enable: true,
        builder_modify: static function (Builder $builder) use ($resolver, $scanController): void {
            $builder->withResolver(static fn (\OpenApi\Resolver $chain) => $chain->setResolvers(new \OpenApi\Utils\TypedList([$resolver])));
            if (!$scanController) {
                $builder->withSpecification(static fn (Specification $spec) => $spec->add(new OA\Operation\Get(
                    path: '/dependencies',
                    x: [SchemaConstants::X_SCHEMA_REQUEST => \Tests\Fixtures\Swagger\Spec\DependencyQuery::class . '@handle'],
                )));
            }
        },
    )))->create();
    $result = $builder->build();
    $doc = $result->toArray();

    foreach (['DependencyQuery', 'DependencyState', 'DependencyResult', 'DependencyAlternative', 'DependencyDetails', 'DependencyLayout'] as $name) {
        $class = 'Tests\\Fixtures\\Swagger\\Spec\\' . $name;
        expect($resolver->classes)->toContain($class)
            ->and((new ReflectionClass($class))->getAttributes(OA\Schema::class))->toBe([]);
    }
    expect($doc['paths']['/dependencies']['get']['parameters'][0]['schema']['$ref'])->toBe('#/components/schemas/DependencyState')
        ->and($doc['components']['schemas']['DependencyState']['enum'])->toBe(['pending', 'completed'])
        ->and($doc['components']['schemas'])->toHaveKeys(['DependencyResult', 'DependencyAlternative', 'DependencyDetails', 'DependencyLayout'])
        ->not->toHaveKey('DependencyQuery')
        ->and($result->specification()->attachables)->toBe([])
        ->and($result->isValid())->toBeTrue()
        ->and($builder->build()->toArray())->toBe($doc);
})->with(['reflected controller' => true, 'specification hook' => false]);

test('ignores static and nonpublic properties', function () {
    $doc = TestFactory::build(['Spec/ClassWithStaticProp.php'])->toArray();
    expect($doc['components']['schemas']['ClassWithStaticProp']['properties'])->toHaveKey('name')->not->toHaveKey('staticName');
});

test('supports default and custom component names and preserves explicit names', function () {
    $doc = TestFactory::build(['SchemaA.php', 'Spec/ExplicitNamedSchema.php'], [
        'schema_name_format_use_classname' => fn (string $class) => 'Custom' . basename(str_replace('\\', '/', $class)),
    ])->toArray();
    expect($doc['components']['schemas'])->toHaveKeys(['CustomSchemaA', 'ExplicitlyNamed']);
});

test('formats nested component references consistently', function () {
    $doc = TestFactory::build(['SchemaDTO.php'], ['schema_name_format_use_classname' => true])->toArray();
    $schemas = $doc['components']['schemas'];
    $name = 'TestsFixturesSwaggerSchemaDTO';
    expect($schemas[$name]['properties']['children']['items']['$ref'])->toBe('#/components/schemas/TestsFixturesSwaggerSchemaDTOChild');
});

test('keeps studly component names for lowercase namespace segments', function () {
    $doc = TestFactory::build(['Spec/lower_case/NamedDTO.php'], ['schema_name_format_use_classname' => true])->toArray();
    expect($doc['components']['schemas'])->toHaveKey('TestsFixturesSwaggerSpecLowerCaseNamedDTO');
});

test('lets the official component namer override the legacy naming config', function () {
    $doc = TestFactory::build(['SchemaDTO.php', 'Spec/ExplicitNamedSchema.php'], [
        'schema_name_format_use_classname' => true,
        'builder_modify' => static fn (Builder $builder) => $builder->setComponentNamer(
            new \OpenApi\ComponentNamer\QualifiedName(strip: 'Tests\\Fixtures\\Swagger\\'),
        ),
    ])->toArray();
    expect($doc['components']['schemas'])->toHaveKeys(['SchemaDTO', 'ExplicitlyNamed'])
        ->and($doc['components']['schemas']['SchemaDTO']['properties']['children']['items']['$ref'])->toBe('#/components/schemas/SchemaDTOChild');
});

test('expands a qualified schema reference without selecting the plain component', function () {
    $class = \Tests\Fixtures\Swagger\Spec\ZeroAndDefaultForm::class;
    $doc = TestFactory::build(config: [
        'schema_name_format_use_classname' => true,
        'builder_modify' => static function (Builder $builder) use ($class): void {
            $builder->withSpecification(static function (Specification $spec) use ($class): void {
                $schema = (new OA\Schema(properties: [
                    new OA\Property(property: 'plain', schema: new OA\Schema(type: 'string')),
                ]))->setReflector(new ReflectionClass($class));
                $variant = (new OA\Schema(properties: [
                    new OA\Property(property: 'selected', schema: new OA\Schema(type: 'integer')),
                ]))->setReflector(new ReflectionClass($class))
                    ->setComponentKey(new \OpenApi\Specification\ComponentKey($class, ['groups' => ['query']]));
                $spec->add($schema, $variant, new OA\Operation\Get(
                    path: '/qualified-query',
                    x: [SchemaConstants::X_SCHEMA_REQUEST => new OA\Schema(ref: new OA\Schema\Ref(
                        ref: $class,
                        qualifiers: ['groups' => ['query']],
                    ))],
                ));
            });
        },
    ])->toArray();
    expect($doc['components']['schemas'])->toHaveKeys([
        'TestsFixturesSwaggerSpecZeroAndDefaultForm',
        'TestsFixturesSwaggerSpecZeroAndDefaultForm-groups.query',
    ])->and($doc['paths']['/qualified-query']['get']['parameters'])->toHaveCount(1)
        ->and($doc['paths']['/qualified-query']['get']['parameters'][0])->toMatchArray([
            'name' => 'selected', 'schema' => ['type' => 'integer'],
        ]);
});

test('generates backing enum values', function () {
    $doc = TestFactory::build(['Spec/PlainEnum.php'])->toArray();
    expect($doc['components']['schemas']['PlainEnum']['enum'])->toBe(['a', 'b']);
});

test('generates unit enum names', function () {
    $doc = TestFactory::build(['Spec/PlainUnitEnum.php'])->toArray();
    expect($doc['components']['schemas']['PlainUnitEnum']['type'])->toBe('string')
        ->and($doc['components']['schemas']['PlainUnitEnum']['enum'])->toBe(['X', 'Y']);
});

test('uses configurable enum descriptions', function () {
    $doc = TestFactory::build(['EnumColor.php'], ['schema_enum_description_method' => 'getDescription'])->toArray();
    expect($doc['components']['schemas']['EnumColor']['description'])->toContain('red: 红色1');
    $disabled = TestFactory::build(['EnumColor.php'], ['schema_enum_description_enable' => false])->toArray();
    expect($disabled['components']['schemas']['EnumColor']['description'])->toBe('颜色枚举');
});

test('preserves DTO constraints types file transport and array item references', function () {
    $doc = TestFactory::build(['SchemaDTO.php'])->toArray();
    $schema = $doc['components']['schemas']['SchemaDTO'];
    $props = $schema['properties'];
    expect($schema['required'])->toContain('name', 'age', 'children')
        ->and($props['name']['minLength'])->toBe(5)
        ->and($props['age']['minimum'])->toBe(1)
        ->and($props['age']['maximum'])->toBe(100)
        ->and($props['children']['items']['$ref'])->toBe('#/components/schemas/SchemaDTOChild')
        ->and($props['child']['$ref'])->toBe('#/components/schemas/SchemaDTOChild')
        ->and($props['date']['format'])->toBe('date-time')
        ->and($props['file']['format'])->toBe('binary')
        ->and($props['fileOrString']['format'])->toBe('binary')
        ->and($props['stringList']['items']['type'])->toBe('string')
        ->and($props['map']['additionalProperties']['type'])->toBe('string');
});

test('preserves false zero empty string and zero bounds', function () {
    $doc = TestFactory::build(['Spec/Integration.php'])->toArray();
    $props = $doc['components']['schemas']['ZeroAndDefaultForm']['properties'];
    expect($props['count'])->toMatchArray(['default' => 0, 'minimum' => 0, 'maximum' => 0])
        ->and($props['text'])->toMatchArray(['default' => '', 'minLength' => 0, 'maxLength' => 0])
        ->and($props['flag']['default'])->toBeFalse()
        ->and($props['values']['default'])->toBe([]);
    expect($doc['components']['parameters']['ZeroAndDefaultForm_X-Required']['required'])->toBeTrue();
});

test('explicit spec values win over inferred DTO values', function () {
    $doc = TestFactory::build(['Spec/Integration.php'])->toArray();
    expect($doc['components']['schemas']['ExplicitSchema']['properties']['value'])->toMatchArray(['type' => 'string', 'maxLength' => 4, 'default' => 'chosen']);
});

test('infers scalar and union handler response types', function (string $version) {
    $doc = TestFactory::build(['Spec/HandlerResponses.php'], ['openapi_version' => $version])->toArray();
    $response = static fn (string $path): array => $doc['paths'][$path]['post']['responses'][200]['content']['application/json']['schema'];
    expect($response('/handler/string')['type'])->toBe('string')
        ->and($response('/handler/union')['oneOf'])->toBe([
            ['$ref' => '#/components/schemas/HandlerResult'],
            ['type' => 'string'],
        ]);
})->with(['3.0.0', '3.1.0']);

test('serializes empty map defaults as objects and preserves array defaults', function (string $version) {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [new ReflectionClass(\Tests\Fixtures\Swagger\Spec\EmptyMapDefaults::class)],
        openapi_version: $version,
        format: 'json',
    );
    $document = (new DocumentBuilder($config))->build()->toArray();
    [$json] = $config->generateWithFormat($document);
    /*
     * 保留对象解码，避免关联数组模式将 {} 和 [] 都转换成 PHP 空数组。
     */
    $props = json_decode($json, false, 512, JSON_THROW_ON_ERROR)->components->schemas->EmptyMapDefaults->properties;
    foreach (['extra', 'names', 'nullableMap', 'withExample'] as $name) {
        expect($props->{$name}->default)->toBeInstanceOf(stdClass::class)
            ->and(get_object_vars($props->{$name}->default))->toBe([]);
    }
    expect($props->list->default)->toBe([])
        ->and($props->nested->default)->toBe([])
        ->and($props->nested->items->default)->toBe([])
        ->and($props->populatedMap->default->key)->toBe('value')
        ->and($props->withExample->example->key)->toBe('example');
    $nullMap = $document['components']['schemas']['EmptyMapDefaults']['properties']['nullMap'];
    expect($nullMap['default'] ?? null)->toBeNull();

    $config->format = 'yaml';
    [$yaml] = $config->generateWithFormat($document);
    $decoded = \Symfony\Component\Yaml\Yaml::parse($yaml, \Symfony\Component\Yaml\Yaml::PARSE_OBJECT_FOR_MAP);
    expect($decoded->components->schemas->EmptyMapDefaults->properties->extra->default)->toBeInstanceOf(stdClass::class)
        ->and($decoded->components->schemas->EmptyMapDefaults->properties->list->default)->toBe([]);
})->with(['3.0.0', '3.1.0']);

test('expands nested arrays and maps with native refs', function () {
    $doc = TestFactory::build(['ExampleDTO'])->toArray();
    $props = $doc['components']['schemas']['UserCreateForm']['properties'];
    expect($props['matrixAddress']['items']['items']['$ref'])->toBe('#/components/schemas/UserCreateFormAddressItem')
        ->and($props['namedMatrixAddress']['additionalProperties']['items']['items']['$ref'])->toBe('#/components/schemas/UserCreateFormAddressItem')
        ->and($props['settings']['additionalProperties']['$ref'])->toBe('#/components/schemas/UserCreateFormSettings')
        ->and($props['id_card']['$ref'] ?? $props['id_card']['oneOf'][0]['$ref'])->toBe('#/components/schemas/UserCreateFormIdCard');
});

test('expands query request schemas and keeps explicit handler response override', function () {
    $doc = TestFactory::build(['ControllerForXSchemaRequest.php'])->toArray();
    $paths = $doc['paths'];
    expect($paths['/get/schema']['get']['parameters'][0]['name'])->toBe('name')
        ->and($paths['/get/schema-multi']['get']['parameters'])->toHaveCount(2)
        ->and($paths['/post/schema']['post']['requestBody']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ControllerForXSchemaRequestSchemaA')
        ->and($paths['/get/schema-with-at-already-has-response']['get']['responses'][200]['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ControllerForXSchemaRequestSchemaA')
        ->and($paths['/get/schema-with-at-union-type']['get']['responses'][200]['headers'])->toHaveKey('header');
});

test('converts boolean parameters into 0/1 including nullable unions', function () {
    $parameters = TestFactory::build(['ControllerForXSchemaRequest.php'])->toArray()['components']['parameters'];
    $flag = $parameters['ControllerForXSchemaRequestSchemaC_flag']['schema'];
    $nullable = $parameters['ControllerForXSchemaRequestSchemaC_nullableFlag']['schema'];
    expect($flag['type'])->toBe('integer')->and($flag['enum'])->toBe([0, 1])
        ->and($nullable['type'])->toBe(['integer', 'null'])->and($nullable['enum'])->toBe([0, 1, null]);
});

test('skips response inference when the handler declares no return type', function () {
    $paths = TestFactory::build(['ControllerForXSchemaRequest.php'])->toArray()['paths'];
    expect($paths['/get/schema-with-at-no-return-type']['get']['responses'])
        ->toBe([200 => ['description' => 'OK']]);
});

test('expands response status maps and compositions', function () {
    $doc = TestFactory::build(['ControllerForXSchemaResponse.php'])->toArray();
    $paths = $doc['paths'];
    expect($paths['/get/schema-multi']['get']['responses'][200]['content']['application/json']['schema']['allOf'])->toHaveCount(2)
        ->and($paths['/get/schema-multi-oneOf']['get']['responses'][200]['content']['application/json']['schema']['oneOf'])->toHaveCount(2)
        ->and($paths['/get/schema-status-code']['get']['responses'][201]['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ControllerForXSchemaResponseSchemaB');
});

test('moves renamed required properties into reusable parameters before cleanup', function () {
    $doc = TestFactory::build(['ControllerForXSchemaRequestBodyProperty.php'], ['clean_unused_components_enable' => true])->toArray();
    $parameters = $doc['components']['parameters'];
    $name = 'ControllerForXSchemaRequestBodyPropertySchema_X-File-Key';
    expect($parameters[$name]['required'])->toBeTrue()
        ->and($parameters[$name]['example'])->toBe('file.txt')
        ->and($doc['paths']['/post/schema-body-property']['post']['requestBody']['content']['application/octet-stream']['schema']['format'])->toBe('binary');
});

test('preserves class tags and skip marker behavior', function () {
    $paths = TestFactory::build(['ControllerWithInfo.php'])->toArray()['paths'];
    expect($paths['/get']['get']['tags'])->toBe(['controller'])
        ->and($paths['/get-more-tag']['get']['tags'])->toBe(['controller', 'more-tag'])
        ->and($paths['/get-skip-class']['get']['tags'])->toBe(['only-tag']);
});

test('builds discriminator variants for request and response', function () {
    $schemas = TestFactory::build(['ExampleDiscriminator'])->toArray()['components']['schemas'];
    foreach (['CreateOrderForm', 'GetOrderResponse'] as $name) {
        expect($schemas[$name]['oneOf'])->toHaveCount(2)
            ->and($schemas[$name]['discriminator']['propertyName'])->toBe('type')
            ->and($schemas[$name . '_type_normal']['properties']['type']['enum'])->toBe(['normal'])
            ->and($schemas[$name . '_type_normal']['properties']['orderData']['$ref'])->toBe('#/components/schemas/CreateOrderFormOrderDataNormal');
    }
    expect($schemas['GetOrderResponse_type_normal']['description'])->toBe('订单详情响应（包含 discriminator）')
        ->and($schemas['GetOrderResponse_type_normal']['properties']['orderData']['description'])->toBe('订单数据（根据 type 决定具体类型）');
});

test('enriches converted properties through the configured docblocks pass after cleanup', function () {
    $parser = new class extends \OpenApi\Utils\DocBlockParser {
        public function parseDocblock(?string $docblock, ?array &$tags = null): string
        {
            $content = parent::parseDocblock($docblock, $tags);

            return \OpenApi\Undefined::isDefault($content) || $content === '' ? $content : 'Parsed: ' . $content;
        }
    };
    $docblocks = new class($parser) extends \OpenApi\Augmenter\Docblocks {
        public int $calls = 0;

        public function __invoke(mixed $payload): mixed
        {
            $this->calls++;

            return parent::__invoke($payload);
        }
    };
    $doc = TestFactory::build(config: [
        'clean_unused_components_enable' => true,
        'builder_modify' => static function (Builder $builder) use ($docblocks): void {
            $builder->getAugmenters()->remove(\OpenApi\Augmenter\Docblocks::class)
                ->insert($docblocks, \OpenApi\Augmenter\OperationIdTemplates::class);
            $builder->withSpecification(static fn (Specification $spec) => $spec->add(new OA\Operation\Post(
                path: '/documented',
                x: [SchemaConstants::X_SCHEMA_REQUEST => \Tests\Fixtures\Swagger\Spec\DocumentedRequest::class . '@handle'],
            )));
        },
    ])->toArray();
    $operation = $doc['paths']['/documented']['post'];
    $parameters = $doc['components']['parameters'];
    $token = $parameters['DocumentedRequest_X-Token'];
    $explicit = $parameters['DocumentedRequest_X-Explicit'];
    $header = $operation['responses'][200]['headers']['X-Count'];
    $request = $operation['requestBody']['content']['application/octet-stream']['schema'];
    $response = $operation['responses'][200]['content']['application/octet-stream']['schema'];
    expect($docblocks->calls)->toBe(1)
        ->and($doc['components']['schemas'] ?? [])->not->toHaveKeys(['DocumentedRequest', 'DocumentedResponse'])
        ->and($token)->toMatchArray(['description' => 'Request token', 'deprecated' => true, 'example' => 'false'])
        ->and($explicit)->toMatchArray(['deprecated' => false, 'example' => '123'])
        ->and($explicit)->not->toHaveKey('description')
        ->and($header)->toMatchArray(['description' => 'Parsed: Response count', 'deprecated' => true, 'example' => 123])
        ->and($request)->toMatchArray(['type' => 'string', 'format' => 'binary', 'description' => 'Parsed: Request content', 'deprecated' => true, 'example' => 'request bytes'])
        ->and($response)->toMatchArray(['type' => 'string', 'format' => 'binary', 'description' => 'Parsed: Response content', 'deprecated' => true, 'example' => 'response bytes']);
});

test('wraps responses with a configured layout and allows per operation opt out', function () {
    $doc = TestFactory::build(['Spec/Integration.php'], ['response_layout_class' => Envelope::class])->toArray();
    $wrapped = $doc['paths']['/spec/request']['post']['responses'][200]['content']['application/json']['schema'];
    expect($wrapped['allOf'][0]['$ref'])->toBe('#/components/schemas/ResponseEnvelope')
        ->and($wrapped['allOf'][1]['properties']['data']['$ref'])->toBe('#/components/schemas/ZeroAndDefaultResponse')
        ->and($doc['paths']['/spec/unwrapped']['get']['responses'][200]['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ZeroAndDefaultResponse');
});

test('inherits parent members without separately collecting its component', function () {
    $doc = TestFactory::build(['SchemaWithParent.php'])->toArray();
    expect($doc['components']['schemas']['SchemaWithParent']['properties'])->toHaveKeys(['name', 'childName']);
});

test('extracts Eloquent properties and applies model visibility', function (string $file, array $present, array $absent) {
    $name = pathinfo($file, PATHINFO_FILENAME);
    $doc = TestFactory::build([$file])->toArray();
    $props = $doc['components']['schemas'][$name]['properties'];
    expect($props)->toHaveKeys($present);
    foreach ($absent as $key) {
        expect($props)->not->toHaveKey($key);
    }
})->with([
    ['SchemaEloquentModel.php', ['id', 'username', 'created_at'], []],
    ['SchemaEloquentHiddenModel.php', ['id', 'username'], ['access_token']],
    ['SchemaEloquentVisibleModel.php', ['id', 'username'], ['created_at']],
    ['SchemaEloquentAbsModel.php', ['created_at'], []],
    ['SchemaEloquentNoDescModel.php', ['id', 'username'], []],
]);

test('keeps unused schemas by default and removes them only when configured', function () {
    expect(TestFactory::build(['SchemaA.php'])->toArray()['components']['schemas'])->toHaveKey('SchemaA');
    $doc = TestFactory::build(['SchemaA.php'], ['clean_unused_components_enable' => true])->toArray();
    expect($doc['components']['schemas'] ?? [])->not->toHaveKey('SchemaA');
});

test('excludes runtime properties declared on custom Eloquent base models', function () {
    $doc = TestFactory::build(['Spec/CustomModel.php'])->toArray();
    $schema = $doc['components']['schemas']['CustomModel'];
    $properties = $schema['properties'] ?? $schema['allOf'][1]['properties'];
    expect($properties)->toHaveKeys(['id', 'documentedField'])
        ->not->toHaveKey('timestamps')
        ->not->toHaveKey('runtimeFlag');
    expect(json_encode($doc['components']['schemas']))->not->toContain('"timestamps":', '"runtimeFlag":');
});

test('sorts components and paths when configured', function () {
    $doc = TestFactory::build(['SchemaB.php', 'SchemaA.php', 'ControllerWithInfo.php'], ['sort_components_sort_paths' => true])->toArray();
    expect(array_keys($doc['components']['schemas']))->toBe(['SchemaA', 'SchemaB']);
    $paths = array_keys($doc['paths']);
    $sorted = $paths;
    sort($sorted);
    expect($paths)->toBe($sorted);
});

test('serializes document configured through builder extension points', function () {
    $response = (new OpenapiController())->openapiDoc([
        'format' => 'json',
        'builder_modify' => function (Builder $builder): void {
            $builder->withSpecification(function (Specification $spec): void {
                $spec->info = new OA\Info(title: 'Configured', version: '1.0');
                $spec->add(new OA\Server(url: '/api'));
            });
        },
    ]);
    $document = json_decode($response->rawBody(), true);
    expect($document['info']['title'])->toBe('Configured')
        ->and($document['servers'])->toBe([['url' => '/api']]);
});

test('generation failures reach the configured error handler', function () {
    $caught = null;
    try {
        (new OpenapiController())->openapiDoc([
            'builder_modify' => function (): void {
                throw new RuntimeException('generation failed');
            },
            'generate_error_handler' => function (Throwable $error) use (&$caught): void {
                $caught = $error;
            },
        ]);
    } catch (RuntimeException $error) {
        expect($caught)->toBe($error)->and($error->getMessage())->toBe('generation failed');
        return;
    }
    test()->fail('Expected generation failure');
});

test('finds XSchema dependencies contributed through withSpecification', function () {
    $doc = TestFactory::build(config: [
        'builder_modify' => function (Builder $builder): void {
            $builder->withSpecification(function (Specification $spec): void {
                $spec->add(new OA\Operation\Post(
                    path: '/from-code',
                    x: [
                        \WebmanTech\Swagger\DTO\SchemaConstants::X_SCHEMA_REQUEST => ZeroAndDefaultForm::class . '@handle',
                    ],
                ));
            });
        },
    ])->toArray();
    expect($doc['paths'])->not->toHaveKey('/__example')
        ->and($doc['paths']['/from-code']['post']['responses'][200]['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ZeroAndDefaultResponse')
        ->and($doc['components']['schemas']['ZeroAndDefaultForm']['properties'])->toHaveKey('count');
});

test('merges method tags with operation and class tags', function () {
    $doc = (new DocumentBuilder(new ConfigOpenapiDocDTO(
        scan_path: [
            new ReflectionClass(\Tests\Fixtures\Swagger\Spec\MethodTagController::class),
            new ReflectionClass(\Tests\Fixtures\Swagger\Spec\SharedTagController::class),
        ],
    )))->build()->toArray();
    expect($doc['paths']['/spec/tags']['get']['tags'])->toBe(['class-tag', 'operation-tag', 'method-tag']);
    expect($doc['paths']['/spec/shared-tags']['get']['tags'])->toBe(['class-tag', 'operation-tag', 'method-tag']);
});

test('keeps both upload media types when a mixed upload precedes a pure file', function () {
    $doc = TestFactory::build(config: [
        'builder_modify' => function (Builder $builder): void {
            $builder->withSpecification(fn (Specification $spec) => $spec->add(new OA\Operation\Post(
                path: '/upload',
                x: [
                    \WebmanTech\Swagger\DTO\SchemaConstants::X_SCHEMA_REQUEST => \Tests\Fixtures\Swagger\Spec\UploadForm::class,
                ],
            )));
        },
    ])->toArray();
    expect($doc['paths']['/upload']['post']['requestBody']['content'])->toHaveKeys(['application/json', 'multipart/form-data'])
        ->and($doc['components']['schemas']['UploadForm']['properties']['file'])->toMatchArray(['type' => 'string', 'format' => 'binary']);
});

test('does not expose Eloquent runtime flags as API fields', function () {
    $props = TestFactory::build(['SchemaEloquentModel.php'])->toArray()['components']['schemas']['SchemaEloquentModel']['properties'];
    expect($props)->toHaveKeys(['id', 'username'])->not->toHaveKeys(['exists', 'incrementing', 'timestamps', 'wasRecentlyCreated']);
});

test('controller accepts reflected scan sources and can reuse a cached document', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [new ReflectionClass(UserController::class)],
        format: 'json',
        cache_key: 'spec-reflection',
    );
    $controller = new OpenapiController();
    $first = $controller->openapiDoc($config)->rawBody();
    $second = $controller->openapiDoc($config)->rawBody();
    expect($second)->toBe($first)
        ->and(json_decode($first, true)['paths'])->toHaveKey('/users');
});

test('validation errors reach the controller error handler', function () {
    $caught = null;
    try {
        (new OpenapiController())->openapiDoc([
            'openapi_validate' => true,
            'builder_modify' => fn (Builder $builder) => $builder->withSpecification(fn (Specification $spec) => $spec->info = new OA\Info()),
            'generate_error_handler' => function (Throwable $error) use (&$caught): void {
                $caught = $error;
            },
        ]);
    } catch (RuntimeException $error) {
        expect($caught)->toBe($error)
            ->and($error->getMessage())->toStartWith('OpenAPI validation failed: ')
            ->and($error->getMessage())->toContain('info.title is required');
        return;
    }
    test()->fail('Expected validation failure');
});

test('uses allOf when parent and child components are both collected', function () {
    $schemas = TestFactory::build(['SchemaB.php', 'SchemaWithParent.php'])->toArray()['components']['schemas'];
    expect($schemas['SchemaWithParent']['allOf'][0]['$ref'])->toBe('#/components/schemas/SchemaB');
});

test('inherits trait properties through the configured attribute factory', function () {
    $schemas = TestFactory::build(['SchemaWithTrait.php'])->toArray()['components']['schemas'];
    expect($schemas['SchemaWithTrait']['allOf'][0]['$ref'])->toBe('#/components/schemas/SchemaWithTraitTrait')
        ->and($schemas['SchemaWithTrait']['allOf'][1]['properties'])->toHaveKey('childName')
        ->and($schemas['SchemaWithTraitTrait']['properties'])->toHaveKey('traitName');
});
