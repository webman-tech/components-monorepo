# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [5.0.18] - 2026-03-13

### Added

- dto+swagger: 默认输出 OpenAPI 3.1 并支持版本可配置

### Changed

- swagger: 将 x-in 子路径 ref 提升为独立组件

### Fixed

- dto+swagger: 修复 map value nullable 丢失
- swagger: 修复 body 属性请求仍追加 json body
- swagger: 收敛 x-in 子路径 ref 保活
- swagger: 修正 docblock @example 的类型转换

## [5.0.17] - 2026-03-11

### Fixed

- swagger: cacheKey 不允许为空字符串

## [5.0.16] - 2026-03-09

### Added

- amis-admin: 记住 CRUD 分页大小
- swagger: 将 DTO 校验规则移到 Operation 描述
- dto+swagger: 支持 discriminator 多态类型识别 (#8)

### Fixed

- dto: 修复 array<string, mixed> 在 OpenAPI 中被错误解析为 array 类型
- swagger: 合并多 request schema 的校验规则

### Changed

- common-utils: 重构 ArrayCache 存储结构并新增自动 GC

## [5.0.15] - 2026-03-03

### Added

- DTO: 支持联合类型验证规则（如 int|string），自动添加 sometimes 验证规则
- common-utils: 添加 ArrayCache 内存缓存实现
- common-utils: 添加 NullCache 空缓存实现

### Fixed

- CI: 忽略依赖安全警告以修复 CI 构建
- CI: 移除已修复的 phpunit 和 symfony/process 安全警告忽略

### Changed

- debugbar: 使用 WeakMap 重构事件绑定机制，防止内存泄漏
- swagger: 使用 common-utils 的缓存实现替换本地实现

## [5.0.14] - 2026-01-30

### Added

- DTO: 新增 `FromDataConfig` trim 配置支持自动去除字符串首尾空格
- DTO: 新增 `FromDataConfig` validatePropertiesAllWithBail 配置
- DTO: 优化 bail 验证规则处理逻辑
- DTO: 新增 `FromDataConfig` validateStopOnFirstFailure 配置
- Logger: 新增命名参数支持,允许使用 level/type/context 参数

### Fixed

- DTO: 修复子项 null 不应在记录日志时被转为 '[NULL]'

### Changed

- CI: 跳过仅修改 markdown 文档的测试运行

## [5.0.13] - 2026-01-27

### Fixed

- DTO: 修复三层嵌套对象的 required_with 验证规则路径问题

## [5.0.12] - 2026-01-20

### Added

- DTO: 新增 `FromDataConfig` 属性支持 ignoreNull 和 ignoreEmpty 数据过滤

## [5.0.11] - 2026-01-19

### Added

- DTO: 新增 shallowValidation 浅层验证参数，用于优化嵌套 DTO 和大数组的验证性能

### Fixed

- 修复 PHPStan Level 9 静态分析错误

## [5.0.10] - 2026-01-16

### Added
- DTO: 嵌套 DTO 验证规则继承和避免重复验证
- amis-admin: 支持带类型的 filter 用于 MongoDB
- amis-admin: 支持时间区间单边查询
- 为所有包添加 CLAUDE.md 文档

## [5.0.9] - 2026-01-06

### Added

- DTO/Swagger: 支持多维数组类型解析和生成（如 `array<string, Xxx[]>`）
- Swagger: 支持提取 `array<string, Xxx[]>` 的形式
- DTO: 支持从 comment 里提取 `array<string, Xxx[]>` 的形式

## [5.0.8] - 2026-01-06

### Added

- Swagger: 支持联合类型的返回
- DTO: 支持 construct 下 array 存在 readonly 的情况

## [5.0.7] - 2025-12-23

### Added

- Logger: 支持 symfony HttpClient 对单接口进行控制日志参数
- Logger: 检查跳过时支持 exception 参数
- Logger: EloquentSQLMessage 增加记录日志后的回调处理（用于告警通知之类的）
- Logger: EloquentSQLMessage bindSql 支持 laravel 低版本的

### Fixed

- 测试字段错误

## [5.0.6] - 2025-12-23

### Added

- Logger: EloquentSQLMessage 支持 extraInfo

## [5.0.5] - 2025-12-19

### Added

- Common-utils: 提供 Ip 的工具类
- Swagger: 支持控制被禁后的文案显示

### Fixed

- Swagger: 注册的路由上到 middleware 错误

## [5.0.4] - 2025-12-16

### Added

- Logger: RequestTraceProcessor 支持在 console 下产出 uid
- 更新依赖

### Fixed

- Logger: 清理测试代码

## [5.0.3] - 2025-12-03

### Fixed

- 修复 runtime dir 问题

## [5.0.2] - 2025-12-02

### Added

- Swagger: 优化使用 psr 缓存，添加更多配置
- Common-utils: middleware 兼容 psr15 handle 的场景
- Logger: 兼容 Auth::guard 不存在的情况
- Common-utils/Swagger: 获取 Url 支持添加 prefix
- Logger: 增加更多 tests，更新 README
- Logger: 优化 cost 计算的代码
- Logger: Add HttpRequestMessage
- Common-utils: from 支持当前 instance
- Common-utils: 优化方法名和测试用例，提高覆盖率
- Common-utils: 调整 Container 的实现逻辑
- Logger: 使用 CommonUtils 下的组件解耦与 webman 的强依赖，重构了部分 Processor 和 Middleware
- Common-utils/Auth: 使用 CommonUtils 下的组件解耦与 webman 的强依赖
- Common-utils/Swagger: 使用 Route 替换原来的 Integrations
- Common-utils: Add Route
- Common-utils: Add BaseMiddleware
- Common-utils: 丰富 Request/Response，增加更多方法和测试
- Swagger: 使用新的 middleware 的形式
- Common-utils: 去除 Request 对 RequestInterface 的依赖，方便扩展
- DTO: 允许将空字符串赋值给允许为 null 的 int 类型
- DTO: 使用 common-utils 下的 request、response
- Common-utils: Add Response/Session
- Common-utils: Add Request
- Logger: add HttpClientMessage
- Logger: add EloquentSQLMessage

### Changed

- Common-utils: 清理无用代码
- Common-utils: 移除非必要依赖
- 所有组件: phpstan fix

### Fixed

- Swagger: path check error

## [5.0.1] - 2025-11-24

### Changed

- 更新依赖版本

## [5.0.0] - 2025-11-24

### Added

- 初始 5.0.0 版本
- 后续组件统一发版（选取原组件版本中最大的，然后再加一位，所以是 5 开始的）

## [1.0.0] - [历史版本]

> **注意**：v5.0.0 之前的版本历史未在此处详细记录。主要变更包括：
>
> - 初始版本发布
> - 各个组件的基础功能实现
> - Webman 框架集成
>
> 如需查看完整的历史变更，请访问 [GitHub Commit 历史](https://github.com/webman-tech/components-monorepo/commits/main/)

[Unreleased]: https://github.com/webman-tech/components-monorepo/compare/v5.0.18...HEAD

[5.0.18]: https://github.com/webman-tech/components-monorepo/compare/v5.0.17...v5.0.18

[5.0.17]: https://github.com/webman-tech/components-monorepo/compare/v5.0.16...v5.0.17

[5.0.16]: https://github.com/webman-tech/components-monorepo/compare/v5.0.15...v5.0.16

[5.0.15]: https://github.com/webman-tech/components-monorepo/compare/v5.0.14...v5.0.15

[5.0.14]: https://github.com/webman-tech/components-monorepo/compare/v5.0.13...v5.0.14

[5.0.13]: https://github.com/webman-tech/components-monorepo/compare/v5.0.12...v5.0.13

[5.0.12]: https://github.com/webman-tech/components-monorepo/compare/v5.0.11...v5.0.12

[5.0.11]: https://github.com/webman-tech/components-monorepo/compare/v5.0.10...v5.0.11

[5.0.10]: https://github.com/webman-tech/components-monorepo/compare/v5.0.9...v5.0.10

[5.0.9]: https://github.com/webman-tech/components-monorepo/compare/v5.0.8...v5.0.9

[5.0.8]: https://github.com/webman-tech/components-monorepo/compare/v5.0.7...v5.0.8

[5.0.7]: https://github.com/webman-tech/components-monorepo/compare/v5.0.6...v5.0.7

[5.0.6]: https://github.com/webman-tech/components-monorepo/compare/v5.0.5...v5.0.6

[5.0.5]: https://github.com/webman-tech/components-monorepo/compare/v5.0.4...v5.0.5

[5.0.4]: https://github.com/webman-tech/components-monorepo/compare/v5.0.3...v5.0.4

[5.0.3]: https://github.com/webman-tech/components-monorepo/compare/v5.0.2...v5.0.3

[5.0.2]: https://github.com/webman-tech/components-monorepo/compare/v5.0.1...v5.0.2

[5.0.1]: https://github.com/webman-tech/components-monorepo/compare/v5.0.0...v5.0.1

[5.0.0]: https://github.com/webman-tech/components-monorepo/releases/tag/v5.0.0
