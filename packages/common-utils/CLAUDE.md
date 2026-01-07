# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

通用工具组件，聚焦"消除 Webman/Laravel/自定义环境差异"，为运行时、HTTP、配置、日志、测试等核心能力提供一致的 API。

**核心功能**：
- **统一运行时抽象**：Runtime + RuntimeCustomRegister 实现多环境自适应
- **HTTP 全栈能力**：Request、Response、Session、Route、Middleware 兼容多个框架
- **配置与环境增强**：Config 支持闭包、EnvAttr 自动加载 env
- **数据处理升级**：Json、Encoding、View 等
- **测试友好**：Factory + 假体让单测可控
- **丰富的 Helper**：base_path()、runtime_path()、logger() 等函数

## 开发命令

测试、静态分析等通用命令与根项目一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 项目架构

### 核心组件
- **Runtime**：
  - `Runtime`：运行时抽象，统一不同环境的路径、容器等
  - `RuntimeCustomRegister`：运行时自定义注册
- **HTTP**：
  - `Request`：统一请求对象，兼容 Webman/Symfony/Laravel
  - `Response`：统一响应对象
  - `Session`：统一 Session 对象
  - `Route`：统一路由对象
  - `Middleware`：中间件基类
- **Config**：
  - `Config`：配置类，支持闭包默认值
  - `EnvAttr`：环境变量管理
- **Helper**：
  - `Json`：JSON 处理，支持 INF/NAN、非 UTF-8
  - `Encoding`：编码修正
  - `View`：纯 PHP 模板渲染
- **Testing**：
  - `Factory`：测试工厂
  - `TestRequest/TestResponse/TestRoute/TestSession`：测试假体
- **Functions**：
  - 全局辅助函数

### 目录结构
- `src/`：
  - `Runtime/`：运行时相关
  - `Http/`：HTTP 相关
  - `Config/`：配置相关
  - `Helper/`：助手类
  - `Support/`：支持类
  - `Functions/`：全局函数
- `src/Install.php`：Webman 安装脚本

测试文件位于项目根目录的 `tests/Unit/CommonUtils/`。

## 代码风格

与根项目保持一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 注意事项

1. **框架兼容性**：代码需要兼容 Webman、Laravel 和自定义环境
2. **适配器模式**：通过适配器实现不同框架的兼容
3. **全局函数**：提供开箱即用的全局函数
4. **测试隔离**：每次测试后自动清理，避免污染
5. **测试位置**：单元测试在项目根目录的 `tests/Unit/CommonUtils/` 下，而非包内
