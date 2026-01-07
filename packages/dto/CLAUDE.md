# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

webman 数据传输对象（DTO）组件，提供完整的数据处理解决方案，包括数据验证、类型转换、请求处理、响应生成等功能。

**核心功能**：
- **自动数据验证**：基于属性注解的验证规则
- **类型自动转换**：基本类型、枚举、日期时间、嵌套对象
- **请求数据处理**：从 HTTP 请求自动提取数据
- **响应数据生成**：对象转数组或 HTTP 响应
- **配置数据处理**：专门处理应用配置
- **序列化控制**：通过注解控制转换行为

## 开发命令

测试、静态分析等通用命令与根项目一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 项目架构

### 核心组件
- **DTO 基类**：
  - `BaseDTO`：基础 DTO 类
  - `BaseRequestDTO`：请求 DTO 类
  - `BaseResponseDTO`：响应 DTO 类
  - `BaseConfigDTO`：配置 DTO 类
- **属性注解**：
  - `ValidationRules`：验证规则
  - `ToArrayFormat`：序列化格式
  - `RequestPropertyIn`：请求属性来源
  - `ToArrayIgnore`：序列化忽略
  - `ToArrayRename`：序列化重命名
- **反射机制**：
  - `ReflectionReaderFactory`：反射读取器工厂
  - `ReflectionClassReader`：类反射读取器
- **Helper**：
  - `DocBlockHelper`：DocBlock 解析
- **Generator**：
  - `DTOGenerator`：DTO 生成器（后端支持）
- **Integrations**：
  - Web 界面相关代码

### 目录结构
- `src/`：
  - `Attributes/`：属性注解
  - `Helper/`：助手类
  - `Reflection/`：反射相关
  - `Generator/`：代码生成器后端
  - `Exception/`：异常类
  - `Integrations/`：集成相关
- `web/`：DTO 代码生成器 Web 界面（构建输出，非核心功能）
- `copy/`：配置文件模板
- `src/Install.php`：Webman 安装脚本

测试文件位于项目根目录的 `tests/Unit/DTO/`。

## 代码风格

与根项目保持一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 注意事项

1. **注解驱动**：主要通过 PHP 属性（Attributes）定义行为
2. **反射机制**：使用反射获取属性类型和注解
3. **类型转换**：自动处理类型转换，包括枚举、日期时间等
4. **验证规则**：支持 Laravel 验证规则
5. **序列化控制**：可以通过注解控制对象到数组的转换
6. **测试位置**：单元测试在项目根目录的 `tests/Unit/DTO/` 下，而非包内

## 附加工具

组件包含一个 Web 界面的 DTO 代码生成器（位于 `web/` 目录），用于从 JSON 生成 DTO 类。源代码和构建说明见根目录的 [webapp/](../../webapp/)。
