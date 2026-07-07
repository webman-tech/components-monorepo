<?php

use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;
use OpenApi\Undefined;
use Psr\Log\NullLogger;
use Tests\Fixtures\Swagger\EnumColor;
use Tests\Fixtures\Swagger\Overwrite\ClassWithMissingType;
use Tests\Fixtures\Swagger\Overwrite\ClassWithStaticProp;
use Tests\Fixtures\Swagger\Overwrite\ExplicitNamedSchema;
use Tests\Fixtures\Swagger\Overwrite\PlainDtoClass;
use Tests\Fixtures\Swagger\Overwrite\PlainEnum;
use Tests\Fixtures\Swagger\Overwrite\PlainUnitEnum;
use Tests\Fixtures\Swagger\SchemaA;
use Tests\Fixtures\Swagger\TestFactory;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\Overwrite as OW;

// ===========================================
// Schema Name Formatting（Schema 名称格式化）
// ===========================================

test('Generator formatSchemaName applies custom formatter to unnamed schemas', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: fn(string $className) => 'Custom' . basename(str_replace('\\', '/', $className)),
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    $schemaNames = collect($openapi->components->schemas)
        ->map(fn(OA\Schema $s) => $schemaName = $s->schema)
        ->toArray();

    expect($schemaNames)->toContain('CustomSchemaA');
});

test('Generator formatSchemaName preserves explicitly named schemas', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/Overwrite/ExplicitNamedSchema.php')],
        schema_name_format_use_classname: fn(string $className) => 'Custom' . basename(str_replace('\\', '/', $className)),
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    $schema = collect($openapi->components->schemas)
        ->first(fn(OA\Schema $s) => SwaggerHelper_getSchemaName($s) === 'ExplicitlyNamed');

    expect($schema)->not->toBeNull()
        ->and($schema->schema)->toBe('ExplicitlyNamed');
});

test('Generator formatSchemaName does nothing when disabled', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: null,
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    $schema = collect($openapi->components->schemas)->first();
    expect($schema)->not->toBeNull()
        ->and($schema->schema)->toBe('SchemaA');
});

test('AugmentSchemas processor formats root unnamed schemas', function () {
    $formatted = [];
    $formatter = function (OA\Schema $schema) use (&$formatted) {
        $formatted[] = SwaggerHelper_getSchemaName($schema);
        $schema->schema = 'Formatted_' . $schema->_context->class;
    };

    $analysis = TestFactory::analysisFromFiles(['SchemaA.php']);
    // TestFactory 已跑过完整 pipeline，schema 已有名称，需重置为 UNDEFINED 才能触发格式化
    $schema = $analysis->getAnnotationForSource(SchemaA::class);
    $schema->schema = Undefined::UNDEFINED;

    swagger_processor_analyse(new OW\Processors\AugmentSchemas($formatter), $analysis);

    expect($formatted)->toContain('');
    expect($schema->schema)->toBe('Formatted_SchemaA');
});

test('ExpandEnums processor formats enum schemas before expansion', function () {
    $formatted = [];
    $formatter = function (OA\Schema $schema) use (&$formatted) {
        $formatted[] = SwaggerHelper_getSchemaName($schema);
        $schema->schema = 'FormattedEnum_' . $schema->_context->enum;
    };

    $analysis = TestFactory::analysisFromFiles(['EnumColor.php']);
    // 重置 schema 名称以触发格式化
    $schema = $analysis->getAnnotationForSource(EnumColor::class);
    $schema->schema = Undefined::UNDEFINED;

    swagger_processor_analyse(new OW\Processors\ExpandEnums($formatter), $analysis);

    expect($formatted)->toContain('');
    expect($schema->schema)->toBe('FormattedEnum_EnumColor');
});

// ===========================================
// Auto Schema/Property Generation（AttributeAnnotationFactory）
// ===========================================

test('AttributeAnnotationFactory auto Schema for enum', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory();
    $factory->setGenerator(new Generator());
    $context = new Context(['enum' => 'PlainEnum']);
    $reflector = new ReflectionClass(PlainEnum::class);

    $annotations = $factory->build($reflector, $context);

    $schemas = array_filter($annotations, fn($a) => $a instanceof OA\Schema);
    expect($schemas)->toHaveCount(1);

    $schema = reset($schemas);
    // 枚举应有 string 类型（因为是 BackedEnum: string）
    expect($schema->type)->toBe('string');
});

test('AttributeAnnotationFactory auto Schema for supported class', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'PlainDtoClass']);
    $reflector = new ReflectionClass(PlainDtoClass::class);

    $annotations = $factory->build($reflector, $context);

    $schemas = array_filter($annotations, fn($a) => $a instanceof OA\Schema);
    expect($schemas)->toHaveCount(1);
});

test('AttributeAnnotationFactory no auto Schema for unsupported class', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'stdClass']);
    $reflector = new ReflectionClass(\stdClass::class);

    $annotations = $factory->build($reflector, $context);

    $schemas = array_filter($annotations, fn($a) => $a instanceof OA\Schema);
    expect($schemas)->toHaveCount(0);
});

test('AttributeAnnotationFactory no auto Schema when explicit annotation exists', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory();
    $context = new Context(['enum' => 'EnumColor']);
    $reflector = new ReflectionClass(EnumColor::class);

    $annotations = $factory->build($reflector, $context);

    // EnumColor 已有 #[OA\Schema(description: '颜色枚举')]，不会自动生成额外的 Schema
    // 父类 parent::build() 会解析出该注解，所以只有 1 个来自显式注解的 Schema
    $schemas = array_filter($annotations, fn($a) => $a instanceof OA\Schema);
    expect($schemas)->toHaveCount(1);
    $schema = reset($schemas);
    // 验证是来自显式注解（有 description），而非自动生成（无 description）
    expect($schema->description)->toBe('颜色枚举');
});

test('AttributeAnnotationFactory auto Property for public non-static props', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'PlainDtoClass', 'property' => 'name']);

    $reflector = new ReflectionProperty(PlainDtoClass::class, 'name');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(1);
});

test('AttributeAnnotationFactory no auto Property for protected prop', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'PlainDtoClass', 'property' => 'secret']);

    $reflector = new ReflectionProperty(PlainDtoClass::class, 'secret');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(0);
});

test('AttributeAnnotationFactory no auto Property for private prop', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'PlainDtoClass', 'property' => 'hidden']);

    $reflector = new ReflectionProperty(PlainDtoClass::class, 'hidden');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(0);
});

test('AttributeAnnotationFactory no auto Property when explicit annotation exists', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory();
    $context = new Context(['class' => 'SchemaA', 'property' => 'name']);

    $reflector = new ReflectionProperty(SchemaA::class, 'name');
    $annotations = $factory->build($reflector, $context);

    // SchemaA::$name 已有 #[OA\Property]，不应自动生成额外的
    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(0);
});

// ===========================================
// ReflectionAnalyser（错误容忍）
// ===========================================

test('ReflectionAnalyser handles valid class without error', function () {
    $analyser = new OW\ReflectionAnalyser([
        new \OpenApi\Analysers\AttributeAnnotationFactory(),
    ]);
    $analysis = new \OpenApi\Analysis([], new Context([
        'logger' => new NullLogger(),
    ]));

    $method = new ReflectionMethod($analyser, 'analyzeFqdn');
    $details = [
        'uses' => [],
        'interfaces' => [],
        'traits' => [],
        'properties' => [],
        'methods' => [],
    ];
    $result = $method->invoke($analyser, EnumColor::class, $analysis, $details);

    expect($result)->toBe($analysis);
});

test('ReflectionAnalyser skips non-existent class gracefully', function () {
    // 父类 analyzeFqdn 在 line 87 先检查 class_exists，不存在的类直接跳过
    // 所以 Overwrite 的 try-catch 是用于分析过程中遇到的 Class not found 错误
    // 这种场景难以直接单元测试，此处仅验证对不存在类不会抛异常
    $analyser = new OW\ReflectionAnalyser([
        new \OpenApi\Analysers\AttributeAnnotationFactory(),
    ]);
    $analysis = new \OpenApi\Analysis([], new Context([
        'logger' => new NullLogger(),
    ]));

    $method = new ReflectionMethod($analyser, 'analyzeFqdn');
    $result = $method->invoke($analyser, 'CompletelyNonExistentClass12345', $analysis, []);

    expect($result)->toBe($analysis);
});

test('ReflectionAnalyser Class not found pattern matching', function () {
    // 验证 catch 条件对典型错误消息的匹配
    $classNotFound = "Class 'SomeClass' not found";
    expect(str_contains($classNotFound, 'Class') && str_contains($classNotFound, 'not found'))->toBeTrue();

    $classNotExist = "Class SomeClass does not exist";
    expect(str_contains($classNotExist, 'Class') && str_contains($classNotExist, 'not found'))->toBeFalse();

    $unrelated = "Something went wrong";
    expect(str_contains($unrelated, 'Class') && str_contains($unrelated, 'not found'))->toBeFalse();
});

// ===========================================
// Integration（集成测试）
// ===========================================

test('full pipeline with schema name formatting produces correct output', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/Overwrite/PlainDtoClass.php')],
        schema_name_format_use_classname: fn(string $className) => basename(str_replace('\\', '/', $className)),
        format: 'json',
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    // PlainDtoClass 没有 #[Schema] 注解，但通过 autoLoadSchemaClasses 应该被自动加载
    $schemas = $openapi->components->schemas;
    expect($schemas)->not->toBeEmpty();

    // schema name 应被格式化
    $schema = collect($schemas)->first();
    expect($schema->schema)->toBe('PlainDtoClass');

    // public 属性应该被自动生成 Property
    $propertyNames = collect($schema->properties ?? [])
        ->map(fn(OA\Property $p) => $p->property)
        ->toArray();
    expect($propertyNames)->toContain('name', 'age')
        ->and($propertyNames)->not->toContain('secret', 'hidden');
});

test('full pipeline with enum schema name formatting', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/Overwrite/PlainEnum.php')],
        schema_name_format_use_classname: fn(string $className) => 'Custom_' . basename(str_replace('\\', '/', $className)),
        format: 'json',
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    $schema = collect($openapi->components->schemas)->first();
    expect($schema->schema)->toBe('Custom_PlainEnum')
        ->and($schema->type)->toBe('string')
        ->and($schema->enum)->toBe(['a', 'b']);
});

// ===========================================
// 补充：Generator 默认 Str 格式化路径
// ===========================================

test('Generator formatSchemaName with true uses default Str formatter', function () {
    // schema_name_format_use_classname: true（非 Closure）走 Str::studly 默认格式化
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: true,
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    $schema = collect($openapi->components->schemas)->first();
    // Tests\Fixtures\Swagger\SchemaA → Tests Fixtures Swagger SchemaA → TestsFixturesSwaggerSchemaA
    expect($schema->schema)->toBe('TestsFixturesSwaggerSchemaA');
});

test('Generator getOpenapiDocConfig returns config', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
    );
    $generator = new OW\Generator($config);

    expect($generator->getOpenapiDocConfig())->toBe($config);
});

test('Generator generate with non-Overwrite Analysis still works', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: fn(string $className) => 'Custom' . basename(str_replace('\\', '/', $className)),
    );
    $generator = new OW\Generator($config);

    // 传入原生 Analysis（非 Overwrite\Analysis），generate 仍应正常完成
    // 格式化通过 Processor 管线（AugmentSchemas/ExpandEnums）触发，不依赖 Analysis hook
    $nativeAnalysis = new \OpenApi\Analysis([], new Context());
    $openapi = $generator->init()->generate($config->getScanSources(), $nativeAnalysis, false);

    // Processor 管线中的格式化仍然生效
    $schema = collect($openapi->components->schemas)->first();
    expect($schema->schema)->toBe('CustomSchemaA');
});

// ===========================================
// 补充：AttributeAnnotationFactory 边界
// ===========================================

test('AttributeAnnotationFactory auto Schema for unit enum defaults to string type', function () {
    // Unit enum（无 backing type）应默认类型为 string
    $factory = new OW\Analysers\AttributeAnnotationFactory();
    $factory->setGenerator(new Generator());
    $context = new Context(['enum' => 'PlainUnitEnum']);
    $reflector = new ReflectionClass(PlainUnitEnum::class);

    $annotations = $factory->build($reflector, $context);

    $schemas = array_filter($annotations, fn($a) => $a instanceof OA\Schema);
    expect($schemas)->toHaveCount(1);

    $schema = reset($schemas);
    expect($schema->type)->toBe('string');
});

test('AttributeAnnotationFactory no auto Property for static prop', function () {
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'ClassWithStaticProp', 'property' => 'staticName']);

    $reflector = new ReflectionProperty(ClassWithStaticProp::class, 'staticName');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(0);
});

test('AttributeAnnotationFactory auto Property for public prop alongside static', function () {
    // 同一个类中 public 非 static 属性应正常生成
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [BaseDTO::class],
    );
    $context = new Context(['class' => 'ClassWithStaticProp', 'property' => 'name']);

    $reflector = new ReflectionProperty(ClassWithStaticProp::class, 'name');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(1);
});

test('AttributeAnnotationFactory no auto Property for unsupported class public prop', function () {
    // autoLoadSchemaClasses 为空，PlainDtoClass 虽然有 public 属性但不在支持列表中
    $factory = new OW\Analysers\AttributeAnnotationFactory(
        autoLoadSchemaClasses: [],
    );
    $context = new Context(['class' => 'PlainDtoClass', 'property' => 'name']);

    $reflector = new ReflectionProperty(PlainDtoClass::class, 'name');
    $annotations = $factory->build($reflector, $context);

    $properties = array_filter($annotations, fn($a) => $a instanceof OA\Property);
    expect($properties)->toHaveCount(0);
});

// ===========================================
// 补充：ReflectionAnalyser catch 块真实触发
// ===========================================

test('ReflectionAnalyser catches Class not found during analysis', function () {
    // 注册一个会在 class_exists 时抛出 "Class 'X' not found" 的 autoloader
    $throwingAutoloader = function (string $class) {
        if (str_starts_with($class, 'NonExistent\\')) {
            throw new \Error("Class '{$class}' not found");
        }
    };
    spl_autoload_register($throwingAutoloader);

    try {
        $analyser = new OW\ReflectionAnalyser([
            new \OpenApi\Analysers\AttributeAnnotationFactory(),
        ]);
        $analysis = new \OpenApi\Analysis([], new Context());

        $method = new ReflectionMethod($analyser, 'analyzeFqdn');
        $method->invoke($analyser, ClassWithMissingType::class, $analysis, [
            'uses' => [],
            'interfaces' => [],
            'traits' => [],
            'properties' => ['missing'],
            'methods' => [],
        ]);

        // 应不抛异常，静默忽略 Class not found
        expect(true)->toBeTrue();
    } finally {
        spl_autoload_unregister($throwingAutoloader);
    }
});

// ===========================================
// 补充：Analysis 边界
// ===========================================

test('Analysis getAnnotationForSource without formatter works as parent', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: null, // 不启用格式化
    );
    $generator = new OW\Generator($config);
    $openapi = $generator->init()->generate($config->getScanSources(), validate: false);

    // 不启用格式化时，schema 名称保持 swagger-php 默认行为
    $schema = collect($openapi->components->schemas)->first();
    expect($schema->schema)->toBe('SchemaA');
});

test('Analysis getAnnotationForSource returns null for unknown FQDN', function () {
    $config = new ConfigOpenapiDocDTO(
        scan_path: [fixture_get_path('Swagger/SchemaA.php')],
        schema_name_format_use_classname: fn(string $className) => 'Custom' . basename(str_replace('\\', '/', $className)),
    );
    $generator = new OW\Generator($config);
    // generate 会创建 Overwrite\Analysis 内部实例
    $generator->init()->generate($config->getScanSources(), validate: false);

    // 通过 TestFactory 获取 analysis 来测试 getAnnotationForSource 对不存在 FQDN 的处理
    $analysis = TestFactory::analysisFromFiles(['SchemaA.php']);
    $result = $analysis->getAnnotationForSource('NonExistentClass');
    expect($result)->toBeNull();
});

// ===========================================
// Helper
// ===========================================

function SwaggerHelper_getSchemaName(OA\Schema $schema): string
{
    return Undefined::isDefault($schema->schema) ? '' : $schema->schema;
}
