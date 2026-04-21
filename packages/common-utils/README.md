# webman-tech/common-utils

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split 出来的，请勿直接修改

> 通用工具组件，聚焦"消除 Webman/Laravel/自定义环境差异"，为运行时、HTTP、配置、日志、测试等核心能力提供一致的 API。

## 核心特点

- **统一运行时抽象**：`Runtime` + `RuntimeCustomRegister` 允许在 Webman、Laravel 与自定义进程中切换，实现路径、容器、日志等核心服务的自适应
- **HTTP 全栈能力**：`Request`、`Response`、`Session`、`Route`、`Middleware\BaseMiddleware` 等类兼容 Webman/Symfony/Laravel 请求对象，并支持附加自定义数据
- **配置与环境增强**：`Config` 支持闭包默认值、配置文件引入；`EnvAttr` 内置 env 文件自动加载、只读保护、系统变量优先级和 define 同步
- **数据处理升级**：`Json` 自动处理 INF/NAN、非 UTF-8 字符以及 `Json\Expression`（注入 JS）；`Encoding` 递归修正编码；`View` 提供纯 PHP 模板渲染
- **测试友好**：`Testing\Factory` 一键注册自定义运行时，配套 `TestRequest/TestResponse/TestRoute/TestSession` 等假体让单测可控、可清理
- **丰富的 Helper**：`base_path()/runtime_path()/logger()/locale()/get_env()` 等函数开箱即用

## 安装

```bash
composer require webman-tech/common-utils
```

安装后无需额外配置，即可在 Webman、Laravel 或测试环境中直接使用。

## 功能模块

### 运行时与基础设施

- **Runtime / RuntimeCustomRegister**：`Runtime` 提供 `isWebman()/isLaravel()/isWorkerman()/isCli()` 等运行时识别方法，可通过 `changeRuntime()` 手动切换。`RuntimeCustomRegister::register()` 允许为路径、容器、日志、请求/响应等关键服务注入自定义实现，完全脱离框架也能运行。`Runtime::terminating()` 在支持的框架中注册请求结束回调。

- **Local**（路径与主机信息）：统一提供 `getBasePath()/getRuntimePath()/getConfigPath()/getAppPath()/getVendorPath()` 等路径方法，支持缓存和自定义注册覆盖。`getIp()` 自动检测当前主机 IPv4，也可通过 `LOCAL_IP` 环境变量强制指定。

- **EnvAttr**（环境变量控制台）：自动加载 `env.local.php` 或 `env.php`，首次调用 `get()` 后默认进入只读模式保证配置一致。支持控制只读开关、系统变量优先级、define 同步，以及批量将 env 写入常量（`transToDefine()`）。

- **Config**：支持闭包默认值（未命中时才执行），以及从当前运行时配置目录引入文件（`requireFromConfigPath()`）。

- **Helper 函数**：`base_path()/runtime_path()/config_path()/app_path()/vendor_path()` 使用 `Local`；`get_env()/put_env()` 操作 `EnvAttr`；`config()` 直通 `Config`；`logger()` 获取日志 channel；`locale()` 读取/设置语言。

### 容器、日志与语言

- **Container**：对接 Webman/Laravel/自定义容器，提供统一的 `get()/has()/make()` API，原始容器可通过 `getRaw()` 获取。
- **Log**：`Log::channel($name)` 自动路由到对应框架日志实例，测试环境默认落在 `Testing\TestLogger`。
- **Lang**：`Lang::getLocale()/setLocale()` 统一语言环境，测试环境通过 `Testing\TestLang` 控制。

### HTTP 相关能力

- **Request**：`getCurrent()` 自动选择当前运行时的请求对象，提供统一的参数获取（`get()/post()/allGet()/allPostJson()` 等）、路由参数（`path()`）、Header/Cookie/原始 Body 读取，以及 `withCustomData()/getCustomData()` 挂载附加信息。

- **Response**：`make()` 根据运行时创建对应响应对象，`from()` 包装已有响应，支持链式调用 `withStatus()/withHeaders()/withBody()` 修改响应内容。

- **Session**：自动适配 Workerman、Symfony、Laravel 等多种 Session 实现，提供统一的 `get()/set()/delete()` API。

- **Route 与 RouteObject**：`getCurrent()` 返回当前路由管理器，支持通过 `RouteObject` 添加路由（含 methods/path/callback/name/middlewares），按名称检索路由，以及生成 URL（含二级目录前缀支持）。`clear()` 用于测试场景清理路由。

- **Middleware\BaseMiddleware**：统一中间件入口，Webman 调用 `process()`，Laravel 调用 `handle()`，最终都落入 `processRequest(Request $request, Closure $handler): Response`，实现的中间件可跨框架复用。

### 数据处理与视图

- **Json**：`encode()` 默认启用 `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`，自动处理 INF/NAN、非 UTF-8 字符、`Json\Expression` 内联 JS，以及 Iterator/JsonSerializable 等特殊类型。`decode()` 遇到 UTF-8 错误会自动尝试转码。
- **Encoding**：`toUTF8()` 支持字符串和数组键值递归转换，对非 UTF-8 字符保底降级为 ASCII，避免 JSON/Web 输出报错。
- **View**：`renderPHP($file, $data)` 以最小成本渲染 PHP 模板，捕获异常时会清理缓冲区避免脏输出。

### 缓存

- **ArrayCache**：符合 PSR-16 标准的内存数组缓存，适用于单次请求内的数据缓存。支持 TTL、默认 TTL 和最大 TTL 限制，支持 LRU 淘汰策略，支持 PSR-20 时钟接口注入便于单元测试，提供 `gc()/count()/keys()` 等辅助方法。
- **NullCache**：空缓存实现（Null Object 模式），所有操作均为空操作，用于禁用缓存或作为依赖注入的默认值。

## 测试工具箱

位于 `WebmanTech\CommonUtils\Testing` 命名空间：

- **Factory::registerTestRuntime($baseDir, $vendorDir)**：将运行时切换为自定义模式，并对路径、容器、配置、日志、语言、request/response/session/route 等关键服务全部注册测试实现，自动加载项目与插件配置。
- **Test* 系列假体**：`TestRequest` 支持自定义任意输入；`TestResponse` 易于断言状态码、Header、Body；`TestRoute/TestContainer/TestSession/TestLogger/TestLang` 提供 `clear()/reset()` 用于隔离测试；`Testing\Webman\ClearableRoute` 解决 Webman 全局路由无法回收的问题。

建议在单测入口处调用 `Factory::registerTestRuntime()`，然后通过 `Request::getCurrent()/Route::getCurrent()` 等公共 API 获取对应假体，保持测试代码与真实环境一致。

## AI 辅助

- **开发维护**：[AGENTS.md](AGENTS.md) — 面向 AI 的代码结构和开发规范说明
- **使用指南**：[skills/webman-tech-common-utils-best-practices/SKILL.md](skills/webman-tech-common-utils-best-practices/SKILL.md) — 面向 AI 的最佳实践，可安装到 Claude Code 的 skills 目录使用
