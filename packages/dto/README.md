# webman-tech/dto

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split 出来的，请勿直接修改

## 简介

webman 数据传输对象（DTO）组件，提供一套完整的数据处理解决方案，包括数据验证、类型转换、请求处理、响应生成等功能。

该组件通过属性注解和反射机制，实现了自动化的数据处理流程，解决了手动处理数据时容易出错、代码重复等问题。

## 功能特性

- **自动数据验证**：基于属性注解的验证规则，支持多种验证类型
- **类型自动转换**：支持基本类型、枚举、日期时间、嵌套对象等类型的自动转换
- **请求数据处理**：从 HTTP 请求中自动提取和处理数据
- **响应数据生成**：将对象转换为数组或 HTTP 响应
- **配置数据处理**：专门处理应用配置数据
- **灵活的序列化控制**：通过注解控制对象到数组的转换行为
- **丰富的属性注解**：提供多种内置注解满足不同场景需求
- **代码生成工具**：提供基于 Web 的界面，可从 JSON 数据自动生成 DTO 类

## 安装

```bash
composer require webman-tech/dto
```

## 核心组件

### BaseDTO 基础数据传输对象

[BaseDTO](src/BaseDTO.php) 是所有 DTO 的基类，提供核心功能，包括从数组创建 DTO 实例（`fromData()`）、将 DTO 转换为数组（`toArray()`）、数据验证和类型转换，以及自定义验证规则支持。

### BaseRequestDTO 请求数据传输对象

[BaseRequestDTO](src/BaseRequestDTO.php) 用于处理 HTTP 请求数据，支持通过 `fromRequest()` 从请求中自动提取数据，并支持多种请求数据来源（Query、Form、JSON、Header、Cookie 等）。

### BaseResponseDTO 响应数据传输对象

[BaseResponseDTO](src/BaseResponseDTO.php) 用于生成 HTTP 响应，通过 `toResponse()` 输出响应，支持自定义响应头、状态码和响应格式。

### BaseConfigDTO 配置数据传输对象

[BaseConfigDTO](src/BaseConfigDTO.php) 用于处理应用配置，通过 `fromConfig()` 从配置创建实例，支持配置合并和默认值，适用于复杂的配置场景。默认不校验传入数据以兼顾性能。

## 属性注解

### ValidationRules 验证规则

[ValidationRules](src/Attributes/ValidationRules.php) 是核心验证注解，支持必填、字符串/整数/数组/枚举/嵌套对象等类型验证、数值范围、字符串长度、自定义规则等多种验证选项。DTO 会自动根据属性的 PHP 类型声明和是否有默认值推断必填和数据类型验证，只在需要额外约束时才需要显式添加注解。

### ToArrayConfig 数组转换配置

[ToArrayConfig](src/Attributes/ToArrayConfig.php) 用于控制 DTO 转换为数组的行为，支持仅包含指定字段（`only`）、排除指定字段（`exclude`）、忽略空值（`ignoreNull`）、空数组转为空对象（`emptyArrayAsObject`）、单字段值直接作为结果（`singleKey`）等选项。也可在调用 `toArray()` 时临时传入覆盖类级别的配置。

### RequestPropertyIn 请求属性来源

用于指定请求中各属性的数据来源，包括：

- [RequestPropertyInQuery](src/Attributes/RequestPropertyInQuery.php)：Query 参数
- [RequestPropertyInForm](src/Attributes/RequestPropertyInForm.php)：Form 数据
- [RequestPropertyInJson](src/Attributes/RequestPropertyInJson.php)：JSON 数据
- [RequestPropertyInHeader](src/Attributes/RequestPropertyInHeader.php)：Header 数据
- [RequestPropertyInCookie](src/Attributes/RequestPropertyInCookie.php)：Cookie 数据

## DTO 代码生成器（Web 版）

仓库提供了一个前端版本的 DTO 代码生成器，构建产物位于 `web/index.html`，特点如下：

- 基于 Vue + CodeMirror，输入 JSON/JSON5 即可预览生成的 PHP DTO 代码
- 支持 Base DTO 与 Form DTO 两种模式，可复制或下载生成结果
- 允许通过 URL 查询参数或 `window.__DTO_GENERATOR_CONFIG` 动态配置默认生成类型、DTO 命名空间等
- 所有资源均被内联，`index.html` + `favicon.svg` 即可离线使用，也方便在 PHP 路由中用 `file_get_contents` 读取后输出

## AI 辅助

- **开发维护**：[AGENTS.md](AGENTS.md) — 面向 AI 的代码结构和开发规范说明
- **使用指南**：[skills/webman-tech-dto-best-practices/SKILL.md](skills/webman-tech-dto-best-practices/SKILL.md) — 面向 AI 的最佳实践，可安装到 Claude Code 的 skills 目录使用
