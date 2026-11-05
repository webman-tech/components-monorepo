# Swagger 组件维护

使用 swagger-php `^7.2` 的纯 `Mode::SPEC`，模型为 `OpenApi\Spec`。7.2.0 已包含属性来源的 Parameter/Header/内联 Schema 文档增强及限定键引用、命名支持。这是直接迁移版，旧 Attributes、Annotations、Generator/Analysis 扩展链不在兼容范围内。

## 生成链路

`Spec/DocumentBuilder` 配置官方 Builder：扫描或 ReflectionClass → AttributeFactory/SchemaTranslator → Assembler → withSpecification → Resolver → Augmenters → Compiler。

- `SchemaTranslator`：DTO 自动 Schema/Property、ValidationRules、请求属性位置及枚举类型；`ModelSchema` 提取 Eloquent PHPDoc 字段。
- `DocumentBuilder` 的 specification hook：预收集 XSchema 和响应包装引用的类，补默认 Info/空文档示例。注册在用户的 specification hooks 之后，使程序化贡献也能被发现。
- `ComponentNamer`：扩展官方 ShortName，只适配旧配置的类名格式；成员名和限定键后缀仍由官方处理。注册在 `builder_modify` 前，允许应用调用 `setComponentNamer()` 替换。
- `OperationTags`：在官方 OperationTags 和 PathItems 后、Merge 前应用类标签优先和 `--class-skip`，此时各个类/方法的标签声明仍完整。
- `DtoAugmenter`：补 required、生成 discriminator 变体；使用带限定信息的 ComponentKey 区分变体，保留原类 reflector。
- `XSchemaAugmenter`：展开请求、响应、参数位置、上传与响应包装。它与 DtoAugmenter 都在 Resolve 组、Refs 之前执行，使新引用参与后续解析和 Cleanup。
- `FinalizeAugmenter`：验证规则描述、枚举描述、内部 x 清理与排序，在 Tags 之前执行。
- `Controller/OpenapiController`：编译结果数组 → JSON/YAML → 缓存。
- `RouteAnnotation/Reader`：从同一 Spec 链路保留的 reflector 和路由 x 元数据提取路由。

## 扩展约定

通过 `builder_modify(Builder): void` 配置官方 extension points；文档修改在 Specification 上完成，编译后直接序列化和缓存。

Spec 的 `Property` 通过 `schema: new OA\Schema(...)` 承载类型约束；组件名用 `component:`。大部分未设置字段为 null，description/example/default 仍可能使用 `Undefined::UNDEFINED`；读取时使用 `SpecHelper::value`，保留 0、false、空字符串和空数组。

`SchemaDependency` 通过官方 `RefInterface`/`RefTrait` 声明临时引用；自定义 resolver 使用 `KeyResolverInterface::resolveKey()`。转换出的 Parameter、Header、二进制 Schema 保留来源 reflector，由官方 Augment 组的 Docblocks 统一补充描述、废弃标记和示例，尊重 builder 配置的 parser。保留 Undefined 与显式 null 的区别；不要额外创建 Docblocks 或重写用户显式的 example/default。

组件索引按桶延迟构建；一旦读取就是该桶当时的快照。DtoAugmenter 的索引仅查询已存在的映射目标，XSchemaAugmenter 复用 Schema 索引并单独维护新增参数的名称集合。引用通过 `getRefValue()` / `getRefKey()` 读取，带限定键的 Schema\Ref 用 `findByKey()` 查找。

贡献需要解析的新类依赖时，使用 `withSpecification` 在 Resolver 前声明。对已收集的依赖做结构转换的 augmenter 必须实现 `PipeInterface<Specification>` 并声明 Resolve 组，在 Refs 前插入；普通 callable 默认进入 Augment 组。组内按插入顺序执行，不能仅凭全局数组位置判断顺序。

## 验证

- `vendor/bin/pest tests/Unit/Swagger --compact`：原控制器/路由快照与 Spec 集成测试。
- `vendor/bin/phpstan analyse --memory-limit=4G --no-progress`：全仓类型检查。
- 真实 HTTP 集成位于 `e2e/webman-src/tests/Feature/SwaggerTest.php`；改对应源码后按根目录 E2E 流程同步、运行。

快照更新前比较解析后的文档语义，区分字段排序、Spec 默认推断与字段丢失。Pest 更新子集快照可能删除其他测试的快照，应检查并恢复无关删除。

用户用法见 README，日常使用建议见 `skills/webman-tech-swagger-best-practices/SKILL.md`，旧版升级方案见其 `references/migrate-attribute-to-spec.md`。
