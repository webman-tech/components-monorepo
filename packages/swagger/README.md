# webman-tech/swagger

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split 出来的，请勿直接修改

## 简介

[Swagger OpenAPI](https://swagger.io/) 在 webman 中的一键配置启用方案，基于 [zircote/swagger-php](https://github.com/zircote/swagger-php) 实现。

该组件基于 swagger-php `^7.2` 的纯 Spec 模式，通过 `OpenApi\Spec` PHP Attributes 或程序化对象定义 API，生成 OpenAPI 文档和 Swagger UI。

## 功能特性

- **零配置启动**：安装后直接访问 `/openapi` 即可看到 Swagger UI 界面
- **多文档支持**：支持单应用下多个 Swagger 文档（多路由，不同 API 文档）
- **动态配置**：支持程序化构建 Spec、扩展生成流水线
- **丰富配置**：支持 host 访问限制、Swagger UI 配置、OpenAPI 配置等
- **性能优化**：服务启动后缓存文档内容，开发环境支持自动更新
- **路由自动注册**：支持自动注册 webman 路由
- **跨框架兼容**：不仅支持 webman 环境，也可在其他环境中使用

## 安装

```bash
composer require webman-tech/swagger
```

## Spec 模式

```php
use OpenApi\Spec as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

#[OA\Tag(name: 'users')]
final class UserController
{
    #[OA\Operation\Post(
        path: '/users',
        x: [SchemaConstants::X_SCHEMA_REQUEST => CreateUserForm::class . '@handle'],
    )]
    public function create() { /* 业务逻辑 */ }
}
```

BaseDTO 子类自动生成 Schema 和公开属性，类型、验证约束、描述与示例分别从 PHP 类型、ValidationRules 和 PHPDoc 提取。`FormClass@handle` 同时使用请求 DTO 和方法返回类型作为响应结构；方法未声明返回类型时只作为请求结构，响应按默认 200 处理。

通过 `builder_modify` 使用官方 extension points，也可不扫描源码，直接贡献 Spec：

```php
use OpenApi\Builder;
use OpenApi\Spec as OA;
use OpenApi\Specification;

'openapi_doc' => [
    'scan_path' => [],
    'builder_modify' => function (Builder $builder): void {
        $builder->withSpecification(function (Specification $spec): void {
            $spec->info = new OA\Info(title: 'My API', version: '1.0');
            $spec->add(new OA\Server(url: '/api'));
            $spec->add(new OA\Operation\Get(
                path: '/health',
                responses: [new OA\Response(response: 200, description: 'OK')],
            ));
        });
        /*
         * 还支持 withAttributeFactory、withResolver、withAugmenters、withMergers。
         */
    },
],
```

组件命名支持原有的 `schema_name_format_use_classname` 配置，也可在 `builder_modify` 中通过官方 `setComponentNamer()` 替换命名策略，例如 `new OpenApi\ComponentNamer\QualifiedName(separator: '.')`。显式 `component:` 名称保持不变，限定键的不同描述由官方生成各自的名称。

### 从旧模式迁移

此版本直接采用 Spec，不提供 classic/hybrid 兼容链路：

| 旧写法 | Spec 写法 |
|---|---|
| `OpenApi\Attributes` / `OpenApi\Annotations` | `OpenApi\Spec` |
| `OA\Get` / `OA\Post` | `OA\Operation\Get` / `OA\Operation\Post` |
| `OA\Property(type: 'string')` | `OA\Property(schema: new OA\Schema(type: 'string'))` |
| `OA\Schema(schema: 'User')` | `OA\Schema(component: 'User')` |
| `generator_modify(Generator)` | `builder_modify(Builder): void` |
| `modify(Annotations\OpenApi): void` | 通过 `builder_modify` 配置 `withSpecification` / `withAugmenters` |

自定义 Processor 需改为操作 Specification 的 augmenter。生成新引用的 augmenter 放在 Resolve 组、Refs 之前。此版本移除 `modify` 配置，文档修改统一通过 `builder_modify` 配置官方扩展点。

完整的升级流程（官方 Rector set、手工补项、验收清单）见 `skills/webman-tech-swagger-best-practices/references/migrate-attribute-to-spec.md`。

## 核心组件

### Swagger 主类

[Swagger](src/Swagger.php) 是主要入口，提供 `registerGlobalRoute()` 注册全局路由和 `registerRoute()` 注册自定义路由两个方法。

### 配置 DTO

#### ConfigRegisterRouteDTO

路由注册配置，控制路由注册行为，包含启用开关（`enable`）、路由前缀（`route_prefix`）、访问权限控制（`host_forbidden`）、Basic 认证（`basic_auth`）、Swagger UI 配置（`swagger_ui`）、OpenAPI 文档配置（`openapi_doc`）、是否注册 webman 路由（`register_route`）及额外中间件（`middlewares`）。

#### ConfigBasicAuthDTO

Basic 认证配置，默认关闭。启用后访问 Swagger UI 和 OpenAPI 文档时需要提供用户名和密码。支持配置用户名（`username`）、密码（`password`）和认证提示域（`realm`）。

#### ConfigOpenapiDocDTO

OpenAPI 文档配置，默认输出版本为 OpenAPI 3.1.0，支持扫描目录或 ReflectionClass（`scan_path`）、排除目录（`scan_exclude`）、文档格式（`format`，yaml/json）、Builder 扩展（`builder_modify`）、缓存键（`cache_key`）及 Schema 相关配置。

#### ConfigSwaggerUiDTO

Swagger UI 配置，支持自定义视图（`view`）、视图路径（`view_path`）、静态资源基础 URL（`assets_base_url`）及视图数据（`data`）。

#### ConfigHostForbiddenDTO

主机访问限制配置，支持启用开关（`enable`）、允许内网访问（`ip_white_list_intranet`）、IP 白名单（`ip_white_list`）及主机白名单（`host_white_list`）。默认仅允许内网环境访问。

### OpenapiController

[OpenapiController](src/Controller/OpenapiController.php) 负责处理文档展示，提供 `swaggerUI()` 展示 Swagger UI 界面和 `openapiDoc()` 返回 OpenAPI 文档内容两个动作。

### Reader 路由注解解析

[Reader](src/RouteAnnotation/Reader.php) 从 Spec Attributes 的反射信息和扩展元数据生成路由配置。传入 `ConfigOpenapiDocDTO` 时复用文档的解析配置（扫描范围、Schema 自动加载等），保证路由与文档一致；`builder_modify` 在启动期不执行，避免依赖请求上下文。

### SchemaConstants 常量定义

[SchemaConstants](src/DTO/SchemaConstants.php) 定义了用于 Schema 的扩展常量，包括 `X_NAME`（命名路由）、`X_PATH`（路由路径）、`X_MIDDLEWARE`（路由中间件）、`X_SCHEMA_REQUEST`（请求 Schema）、`X_SCHEMA_RESPONSE`（响应 Schema）。

## 参考示例

- [webman 使用最佳实践](https://github.com/krissss/webman-basic/tree/master/app/api)
- [webman 使用 swagger 示例：注解模式的 crud](https://github.com/webman-tech/webman-samples/tree/swagger-attributions)
- [webman 使用 swagger 示例：多 swagger 文档](https://github.com/webman-tech/webman-samples/tree/swagger-multi)

## AI 辅助

- **开发维护**：[AGENTS.md](AGENTS.md) — 面向 AI 的代码结构和开发规范说明
- **使用指南**：[skills/webman-tech-swagger-best-practices/SKILL.md](skills/webman-tech-swagger-best-practices/SKILL.md) — 面向 AI 的最佳实践，可安装到 Claude Code 的 skills 目录使用
