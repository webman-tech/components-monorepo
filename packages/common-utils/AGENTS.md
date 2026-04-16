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

测试、静态分析等通用命令与根项目一致，详见根目录 [AGENTS.md](../../AGENTS.md)。

## 目录结构
- `src/`：
  - `Runtime.php`：运行时抽象，统一不同环境的路径、容器等核心能力
  - `RuntimeCustomRegister.php`：运行时自定义注册
  - `Request.php`/`Response.php`/`Session.php`/`Route.php`：HTTP 层统一封装，兼容 Webman/Symfony/Laravel
  - `Config.php`：配置类，支持闭包默认值
  - `EnvAttr.php`：环境变量管理
  - `Json.php`：JSON 处理，支持 INF/NAN、非 UTF-8
  - `Encoding.php`：编码修正
  - `View.php`：纯 PHP 模板渲染
  - `Middleware/`：BaseMiddleware 基类
  - `Cache/`：ArrayCache/NullCache
  - `Testing/`：
    - `Factory.php`：测试工厂，注册测试运行时
    - `Test*.php`：各类测试假体（Request/Response/Route/Session/Config/Container 等）
  - `functions.php`：全局辅助函数（base_path()/runtime_path()/logger() 等）
- `src/Install.php`：Webman 安装脚本

测试文件位于项目根目录的 `tests/Unit/CommonUtils/`。测试环境配置和 Helper 函数详见根目录 [AGENTS.md](../../AGENTS.md) 的测试相关章节。

## 工作流程

```
业务代码
    │
    ▼
common-utils 统一 API
(Request / Response / Config / Log / Session / Route...)
    │
    ▼
Runtime (运行时自动检测 / RuntimeCustomRegister 手动注册)
    │
    ├── Webman 环境
    ├── Laravel 环境
    └── 自定义环境
```

测试时通过 `Factory::registerTestRuntime()` 注入测试假体，业务代码无需修改。

## 代码风格

与根项目保持一致，详见根目录 [AGENTS.md](../../AGENTS.md)。

## 注意事项

1. **框架兼容性**：代码需要兼容 Webman、Laravel 和自定义环境
2. **适配器模式**：通过适配器实现不同框架的兼容
3. **全局函数**：提供开箱即用的全局函数
4. **测试隔离**：每次测试后自动清理，避免污染
