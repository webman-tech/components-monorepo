<?php

declare(strict_types=1);

namespace WebmanTech\Swagger\DTO;

use Closure;
use OpenApi\Builder;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use WebmanTech\CommonUtils\Cache\ArrayCache;
use WebmanTech\CommonUtils\Cache\NullCache;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigOpenapiDocDTO extends BaseConfigDTO
{
    public bool $openapi_validate;
    public array $auto_load_schema_classes;
    public iterable $scan_path;

    public function __construct(
        string|iterable|Closure    $scan_path = [], // 扫描的目录
        public null|array          $scan_exclude = null, // 扫描忽略的
        public null|Closure        $builder_modify = null, // 配置纯 Spec Builder（Closure(Builder): void）
        public null|string|Closure $cache_key = null, // 缓存用的 key，当注册不同实例时，需要指定不同的 key，或者做热更新用
        public null|Closure        $cache_instance = null, // 缓存实例
        public null|Closure        $generate_error_handler = null, // 生成失败时的处理，可以用于做通知提醒
        public string              $format = 'yaml', // yaml/json
        public null|int            $max_execute_time = null, // 运行时间限制，单位 s
        public null|int            $max_memory_usage = null, // 运行内存限制，单位 M
        null|bool                  $openapi_validate = null, // 是否校验产出的 openapi 文档
        public bool                $enable_dto_generator = true, // 是否显示 DTO 生成器链接
        public null|array          $dto_generator_config = null, // DTO 生成器配置
        public null|true|Closure   $schema_name_format_use_classname = null, // schema 的名称是否使用完整的类名（swagger-php 默认取类的名字，不带 namespace）
        public bool                $schema_enum_description_enable = true, // 提取 enum 的描述信息开关
        public null|string         $schema_enum_description_method = null, // 指定提取 enum 的描述信息的方法名
        public bool                $expand_eloquent_model_enable = true, // 是否自动扫描 Eloquent Model 的属性，并生成对应的 schema
        public bool                $append_dto_validation_rules_in_description = true, // 是否将 DTO 的验证规则写入到 Operation 的 description 中
        public bool                $clean_unused_components_enable = false, // 是否清理未使用的 components
        public bool                $sort_components_enable = true, // 是否对 components 进行排序
        public bool                $sort_components_sort_schemas = true, // 是否对 components.schemas 进行排序
        public bool                $sort_components_sort_paths = false, // 是否对 components.paths 进行排序
        null|array                 $auto_load_schema_classes = null, // 自动加载 schema 类型的 class 名集合，可以在不需要写 Schema 注解的情况下也能当 Schema 用
        public null|string         $response_layout_class = null, // response 的结构 class
        public null|string         $response_layout_data_code = null, // response 的结构 class 中的 data 字段
        public string              $openapi_version = '3.1.0', // OpenAPI 版本号，支持 3.0.0 / 3.1.0 等
    )
    {
        $this->auto_load_schema_classes = $auto_load_schema_classes ?? [];

        $this->openapi_validate = $openapi_validate ?? false;

        if ($scan_path instanceof Closure) {
            $scan_path = $scan_path();
        }
        if (is_string($scan_path) || $scan_path instanceof Finder) {
            $scan_path = [$scan_path];
        }
        $this->scan_path = $scan_path;
    }

    protected static function getAppConfig(): array
    {
        return ConfigHelper::get('app.openapi_doc', []);
    }

    public function getScanSources(): iterable
    {
        /** @var array $scanPaths */
        $scanPaths = $this->scan_path;
        // 区分 files 和 path
        $files = [];
        $finders = [];
        $paths = [];
        foreach ($scanPaths as $path) {
            if (is_iterable($path) && !$path instanceof Finder) {
                $files[] = (new self(scan_path: $path, scan_exclude: $this->scan_exclude))->getScanSources();
                continue;
            }
            if ($path instanceof \ReflectionClass) {
                $files[] = $path;
                continue;
            }
            if ($path instanceof Finder) {
                $finders[] = $path;
                continue;
            }
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }
            $paths[] = $path;
        }

        return [
            $files,
            $finders,
            $paths
                ? Finder::create()
                ->files()
                ->followLinks()
                ->name('*.php')
                ->in($paths)
                ->exclude($this->scan_exclude ?? [])
                : []
        ];
    }

    public function getCacheKey(): string
    {
        $cacheKey = null;
        if (is_string($this->cache_key)) {
            $cacheKey = $this->cache_key;
        } elseif ($this->cache_key instanceof Closure) {
            $cacheKey = call_user_func($this->cache_key, $this);
        }

        return $cacheKey ?: 'swagger:openapi_doc';
    }

    private ?CacheInterface $cache = null;

    public function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            $cache = $this->cache_instance ?? new ArrayCache(defaultTtl: 3600, maxItems: 100);
            if ($cache instanceof Closure) {
                $cache = call_user_func($cache);
                /** @var CacheInterface|false $cache */
            }
            if ($cache === false) {
                $cache = new NullCache();
            }
            $this->cache = $cache;
        }

        return $this->cache;
    }

    /**
     * 将应用配置接入官方 Builder 扩展点
     */
    public function applyBuilder(Builder $builder): void
    {
        if ($this->builder_modify !== null) {
            ($this->builder_modify)($builder);
        }
    }

    /**
     * 序列化编译结果，保留空对象与空数组的区别
     *
     * @return array{string, string}
     */
    public function generateWithFormat(array $document): array
    {
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return match ($this->format) {
            'json' => [$json, 'application/json;charset=utf-8'],
            default => [
                Yaml::dump(
                    json_decode($json, false, 512, JSON_THROW_ON_ERROR),
                    10,
                    2,
                    Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE,
                ),
                'application/yaml;charset=utf-8',
            ],
        };
    }
}
