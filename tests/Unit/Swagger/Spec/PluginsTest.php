<?php

declare(strict_types=1);

use OpenApi\Augmenter\Cleanup;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Spec as OA;
use OpenApi\Specification;
use Tests\Fixtures\Swagger\TestFactory;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\Plugin\Core\SpecCorePlugin;
use WebmanTech\Swagger\Plugin\Core\SpecificationContribution;
use WebmanTech\Swagger\Plugin\Dto\DtoPlugin;
use WebmanTech\Swagger\Plugin\Eloquent\EloquentPlugin;
use WebmanTech\Swagger\Plugin\NativeEnum\NativeEnumPlugin;
use WebmanTech\Swagger\Plugin\SymfonyHttp\SymfonyHttpPlugin;
use WebmanTech\Swagger\Plugin\WebmanHttp\WebmanHttpPlugin;
use WebmanTech\Swagger\Spec\DocumentBuilder;
use WebmanTech\Swagger\Spec\Translator\UploadSchemaTranslator;

test('source plugins work with the official builder independently of the component core', function (string $source, string $plugin, string $component, array $expected) {
    $result = (new Builder())
        ->setMode(Mode::SPEC)
        ->addSource(fixture_get_path('Swagger/' . $source))
        ->withPlugins([$plugin])
        ->withAugmenters(static fn ($pipeline) => $pipeline->get(Cleanup::class)?->setEnabled(false))
        ->withSpecification(static fn (Specification $spec) => $spec->info = new OA\Info(title: 'Plugin', version: '1'))
        ->build();

    expect($result->toArray()['components']['schemas'][$component])->toMatchArray($expected);
})->with([
    'dto' => ['Spec/PlainDtoClass.php', DtoPlugin::class, 'PlainDtoClass', [
        'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']],
    ]],
    'eloquent' => ['SchemaEloquentHiddenModel.php', EloquentPlugin::class, 'SchemaEloquentHiddenModel', [
        'properties' => [
            'id' => ['type' => 'integer', 'description' => '(主键)'],
            'username' => ['type' => 'string', 'description' => '用户名'],
            'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => '创建时间'],
        ],
    ]],
    'enum' => ['Spec/PlainEnum.php', NativeEnumPlugin::class, 'PlainEnum', [
        'type' => 'string', 'enum' => ['a', 'b'],
    ]],
]);

test('Symfony upload integration works on explicit spec properties without DTO support', function () {
    $builder = (new Builder())->setMode(Mode::SPEC)
        ->addSource(fixture_get_path('Swagger/Spec/SymfonyUpload.php'))
        ->withPlugins([SymfonyHttpPlugin::class])
        ->withAugmenters(static fn ($pipeline) => $pipeline->get(Cleanup::class)?->setEnabled(false));
    $doc = $builder->build()->toArray();
    $uploads = $builder->getAttributeFactory()->getTranslators()->get(UploadSchemaTranslator::class);

    expect($doc['components']['schemas']['SymfonyUpload']['properties']['file'])->toMatchArray([
        'type' => 'string', 'format' => 'binary',
    ])
        ->and($uploads->supports(\Symfony\Component\HttpFoundation\File\UploadedFile::class))->toBeTrue()
        ->and($uploads->supports(\Webman\Http\UploadFile::class))->toBeFalse();
});

test('native spec models and enums work without inheriting DTO classes', function () {
    require_once fixture_get_path('Swagger/Spec/NativeApi.php');
    $result = (new DocumentBuilder(new ConfigOpenapiDocDTO(
        scan_path: [new ReflectionClass(\Tests\Fixtures\Swagger\Spec\NativeController::class)],
        openapi_validate: true,
    )))->build();
    $doc = $result->toArray();

    expect($result->isValid())->toBeTrue()
        ->and($doc['paths']['/native']['get']['parameters'][0])->toMatchArray([
            'name' => 'id', 'in' => 'query', 'example' => 0,
        ])
        ->and($doc['components']['schemas']['NativeStatus'])->toMatchArray([
            'type' => 'string', 'enum' => ['active'],
        ]);
});

test('HTTP plugins share one translator per builder without leaking upload types', function () {
    $builder = (new Builder())->withPlugins([WebmanHttpPlugin::class, SymfonyHttpPlugin::class]);
    $translators = $builder->getAttributeFactory()->getTranslators();
    $uploads = $translators->get(UploadSchemaTranslator::class);
    $count = count(array_filter(iterator_to_array($translators), static fn ($translator) => $translator instanceof UploadSchemaTranslator));
    $other = (new Builder())->withPlugins([SymfonyHttpPlugin::class])
        ->getAttributeFactory()->getTranslators()->get(UploadSchemaTranslator::class);

    expect($count)->toBe(1)
        ->and($uploads->supports(\Webman\Http\UploadFile::class))->toBeTrue()
        ->and($uploads->supports(\Symfony\Component\HttpFoundation\File\UploadedFile::class))->toBeTrue()
        ->and($other->supports(\Webman\Http\UploadFile::class))->toBeFalse();
});

test('DTO schema augmentation runs before XSchema expansion regardless of registration order', function (bool $coreFirst) {
    require_once fixture_get_path('Swagger/Spec/Integration.php');
    $plugins = $coreFirst ? [SpecCorePlugin::class, DtoPlugin::class] : [DtoPlugin::class, SpecCorePlugin::class];
    $doc = (new Builder())->setMode(Mode::SPEC)
        ->addSource(fixture_get_path('Swagger/Spec/Integration.php'))
        ->withPlugins($plugins)
        ->withSpecification(new SpecificationContribution())
        ->build()->toArray();

    expect($doc['components']['parameters']['ZeroAndDefaultForm_X-Required']['required'])->toBeTrue()
        ->and($doc['paths']['/spec/request']['post']['description'])->toContain('Validation Rules')
        ->and($doc['paths']['/spec/request']['post']['responses'][200]['content']['application/json']['schema']['$ref'])
        ->toBe('#/components/schemas/ZeroAndDefaultResponse');
})->with([true, false]);

test('application hooks can override the core plugin cleanup setting', function () {
    $doc = TestFactory::build(['SchemaA.php'], [
        'clean_unused_components_enable' => false,
        'builder_modify' => static function (Builder $builder): void {
            $builder->withAugmenters(static fn ($pipeline) => $pipeline->get(Cleanup::class)?->setEnabled(true));
        },
    ])->toArray();

    expect($doc['components']['schemas'] ?? [])->not->toHaveKey('SchemaA');
});
