# webman-tech/common-utils

本项目由 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
得到，请勿在该仓库直接修改。

> 通用工具组件，聚焦“消除 Webman/Laravel/自定义环境差异”，为运行时、HTTP、配置、日志、测试等核心能力提供一致的 API。

## 核心特点

- **统一运行时抽象**：`Runtime` + `RuntimeCustomRegister` 允许在 Webman、Laravel 与自定义进程中切换，实现路径、容器、日志等核心服务的自适应。
- **HTTP 全栈能力**：全新的 `Request`、`Response`、`Session`、`Route`、`Middleware\BaseMiddleware` 等类兼容
  Webman/Symfony/Laravel 请求对象，并支持附加自定义数据。
- **配置与环境增强**：`Config` 支持闭包默认值、配置文件引入；`EnvAttr` 内置 env 文件自动加载、只读保护、系统变量优先级和
  define 同步。
- **数据处理升级**：`Json` 自动处理 INF/NAN、非 UTF-8 字符以及 `Json\Expression`（注入 JS）；`Encoding` 递归修正编码；`View`
  提供纯 PHP 模板渲染。
- **测试友好**：`Testing\Factory` 一键注册自定义运行时，配套 `TestRequest/TestResponse/TestRoute/TestSession`
  等假体让单测可控、可清理。
- **丰富的 Helper**：`base_path()/runtime_path()/logger()/locale()/get_env()` 等函数开箱即用，帮助在业务层快速接入。

## 安装

```bash
composer require webman-tech/common-utils
```

安装后无需额外配置，即可在 Webman、Laravel 或测试环境中直接使用。

## 快速体验

```php
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\Json;

$request = Request::getCurrent();

$userId = $request->path('id');
$payload = $request->allPostJson();

$response = Response::make()
    ->withStatus(201)
    ->withHeaders(['X-Trace-Id' => $request->header('X-Trace-Id')])
    ->withBody(Json::encode(['id' => $userId, 'payload' => $payload]));

return $response->getRaw(); // 始终返回原生 Webman/Symfony 响应
```

## 功能模块

### 运行时与基础设施

- **Runtime / RuntimeCustomRegister**
    - `Runtime::isWebman()/isLaravel()/isWorkerman()/isCli()`：准确识别当前运行时，可通过 `Runtime::changeRuntime()`
      手动切换（测试/自定义场景）。
    - `RuntimeCustomRegister::register()`：为 `base_path/runtime_path/config_get/request/response/...`
      等关键服务注入自定义实现，完全脱离框架也能运行。
    - `Runtime::terminating()`：在支持的框架中注册请求结束回调。

- **Local**（路径与主机信息）
    - `getBasePath()/getRuntimePath()/getConfigPath()/getAppPath()/getVendorPath()`：统一返回路径并支持缓存，可通过自定义注册覆盖。
    - `combinePath()`：跨平台拼接路径。
    - `getIp()`：自动检测当前主机 IPv4（支持 Windows/Linux/macOS），也可通过 `LOCAL_IP` 环境变量强制指定。

- **EnvAttr**（环境变量控制台）
    - 自动加载 `env.local.php` 或 `env.php`，首次调用 `get()` 后默认变为只读，保证配置一致。
    - `changeSupportReadonly/changeSupportSysEnv/changeSupportDefine` 控制只读开关、系统变量优先级、define 支持。
    - `transToDefine()` 可批量把 env 写入常量，便于兼容老代码。

- **Config**
    - 支持 `Config::get('foo.bar', fn () => 'default')` 闭包默认值，未命中时才执行。
    - `Config::requireFromConfigPath('database')` 从当前运行时配置目录引入文件。

- **Helper 函数**
    - `base_path()/runtime_path()/config_path()/app_path()/vendor_path()` 使用 `Local`。
    - `get_env()/put_env()` 操作 `EnvAttr`；`config()` 直通 `Config`。
    - `logger()` 获取 `Log::channel()`；`locale()` 读取/设置语言。

### 容器、日志与语言

- **Container**：对接 Webman/Laravel/自定义容器，`get()/has()/make()` API 一致，原始容器可通过 `Container::getRaw()` 获取。
- **Log**：`Log::channel($name)` 自动路由到对应框架日志实例，测试环境默认落在 `Testing\TestLogger`。
- **Lang**：`Lang::getLocale()/setLocale()` 统一语言环境，在测试环境通过 `Testing\TestLang` 控制。

### HTTP 相关能力

- **Request**
    - `Request::getCurrent()` 自动选择 Webman、Laravel、Symfony Request 或自注册对象。
    - `get()/post()/postForm()/postJson()/allGet()/allPostForm()/allPostJson()` 统一获得参数，包含
      query、form-data、json、文件。
    - `path()` 读取路由参数，`header()/cookie()/rawBody()` 获取标准数据，`getContentType()` 自动转小写。
    - `getRoute()` 返回 `Route\RouteObject`，`getSession()` 返回 `Session` 封装。
    - `getUserIp()` 对 Webman/Symfony 做了真实 IP 解析；`getHost()` 返回访问域名。
    - `withHeaders()` 修改真实请求头；`withCustomData()/getCustomData()` 可挂载附加信息（Webman 中通过动态属性存储）。

- **Response**
    - `Response::make()` 根据运行时创建 Webman/Symfony/TestResponse；`Response::from()` 包装已有响应。
    - 链式调用 `withStatus()/withHeaders()/withBody()` 修改原生响应；`getStatusCode()/getBody()/getHeader()` 读取最终内容。

- **Session**
    - 自动适配 `Workerman\Session`、`Symfony\SessionInterface`、`Illuminate\Contracts\Session\Session` 等多种实现。
    - 提供统一的 `get()/set()/delete()`。

- **Route 与 RouteObject**
    - `Route::getCurrent()` 返回当前路由管理器（Webman 读取全局 Route，自定义环境使用 `Testing\TestRoute`）。
    - `addRoute()` 接收 `RouteObject`，可带 methods/path/callback/name/middlewares。
    - `getRouteByName()/getRoutes()` 支持按名称检索和批量遍历；`RouteObject::getUrl()` 可以调用底层路由器生成 URL。
    - `Route::clear()` 清理运行时路由（内置 `Testing\Webman\ClearableRoute`，解决单测间互相污染）。

- **Middleware\BaseMiddleware**
    - 统一入口：Webman 调用 `process()`，Laravel 调用 `handle()`，最终都落入
      `processRequest(Request $request, Closure $handler): Response`。
    - 通过 `Request/Response` 适配层，开发者只需要面向公共 API，实现的中间件即可跨框架复用。

### 数据处理与视图

- **Json**
    - `Json::encode()` 默认启用 `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`，同时支持：
        - 自动把 INF/NAN 转为 `0`（或在设置 `JSON_PARTIAL_OUTPUT_ON_ERROR` 后继续输出）。
        - 自动递归调用 `Encoding::toUTF8()`，在遇到非 UTF-8 字符时重试。
        - 将 `Json\Expression` 内联输出 JS 代码，常用于前后端混合渲染。
        - 对 `Iterator/JsonSerializable/SimpleXMLElement/DateTimeInterface/资源` 等类型做特殊处理。
    - `Json::decode()` 遇到 UTF-8 错误会自动尝试转码。

- **Encoding**
    - `Encoding::toUTF8()` 支持字符串、数组键值递归转换，对非 UTF-8 字符保底降级为 ASCII 字符，避免 JSON/Web 输出报错。

- **View**
    - `View::renderPHP($file, $data)` 以最小成本渲染 PHP 模板，捕获异常时会清理缓冲区，避免脏输出。

## 测试工具箱

位于 `WebmanTech\CommonUtils\Testing` 命名空间，配合 `Testing\Factory` 构建完全可控的运行时：

- **Factory::registerTestRuntime($baseDir, $vendorDir)**
    - 将运行时切换为 `Runtime::changeRuntime(Constants::RUNTIME_CUSTOM)`，并对 base/runtime/config/app/vendor
      path、容器、配置、日志、语言、request/response/session/route 等关键服务全部注册测试实现。
    - 自动加载项目与插件配置、初始化数据库（如果 Webman 相关类存在）。

- **Test* 系列**
    - `TestRequest`：可通过 `setData()/setHeader()/setGet()/setPost()` 自定义任意输入，并支持 `withCustomData()`。
    - `TestResponse`：易于断言状态码、Header、Body。
    - `TestRoute/TestContainer/TestSession/TestLogger/TestLang`：帮助模拟底层组件，提供 `clear()/reset()` 用于隔离测试。
    - `Testing\Webman\ClearableRoute`：解决 Webman 全局路由无法回收的问题。

> 建议在单测入口处调用 `Factory::registerTestRuntime()`，然后通过 `Request::getCurrent()`/`Route::getCurrent()` 等公共
> API 获取对应假体，这样测试代码与真实环境保持一致。

## 使用示例

### 统一的中间件

```php
use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;

final class TraceMiddleware extends BaseMiddleware
{
    protected function processRequest(Request $request, \Closure $handler): Response
    {
        $request->withHeaders(['X-Request-Trace' => uniqid('trace_', true)]);

        $response = $handler($request);

        return $response->withHeaders(['X-Response-Time' => microtime(true)]);
    }
}
```

这段中间件既可以在 Webman `process()` 中注册，也可在 Laravel `handle()` 中使用，完全复用。

### 操作路由表

```php
use WebmanTech\CommonUtils\Route;
use WebmanTech\CommonUtils\Route\RouteObject;

$route = Route::getCurrent();

$route->addRoute(new RouteObject(
    methods: ['GET', 'POST'],
    path: '/users/{id}',
    callback: [UserController::class, 'detail'],
    name: 'users.detail',
    middlewares: ['auth', 'throttle:60,1'],
));

$item = $route->getRouteByName('users.detail')
$detailUrl = $item->getUrl(['id' => 1]); // Webman 环境下可生成 /users/1
$detailUrl = $item->getUrl(['id' => 1], appendPrefix: true); // 如果项目在二级目录下，可以开启该配置，Webman 环境下可生成 /{YourPrefix}/users/1
```

### 加强版 env 管理

```php
use WebmanTech\CommonUtils\EnvAttr;

EnvAttr::reset();
EnvAttr::changeSupportReadonly(false);
EnvAttr::set('PAYMENT_GATEWAY', 'stripe');
EnvAttr::changeSupportSysEnv(false); // 让自定义 env 优先

$gateway = EnvAttr::get('PAYMENT_GATEWAY', fn () => 'fallback');

EnvAttr::changeSupportDefine(true);
EnvAttr::transToDefine(['exclude' => ['SENSITIVE_KEY']]);
```

## 最佳实践

1. **只依赖公共 API**：应用代码中应调用 `Request/Response/Route/Container` 等抽象层，禁止直接引用具体框架类。
2. **运行时切换**：编写命令行或测试脚本时，先 `Runtime::changeRuntime()` 或调用 `Testing\Factory::registerTestRuntime()`
   ，确保路径、容器等逻辑一致。
3. **善用辅助函数**：路径、配置、日志、语言的 Helper 能减少大量 `RuntimeCustomRegister` 判断。
4. **中间件场景**：`BaseMiddleware` 能确保修改请求头/自定义数据真实写回原请求对象，避免多框架行为不一致。
5. **Json/Encoding**：当需要序列化复杂数据时优先使用 `Json::encode()`，避免原生 `json_encode()` 触发 UTF-8/INF/NAN 错误。
6. **测试隔离**：测试完成后重置 `TestRequest/TestResponse/TestContainer/TestRoute`，或者使用内置 `clear()` 方法避免跨用例污染。

## 注意事项

- 组件会根据当前运行时自动选择实现，若运行时不可识别且未通过 `RuntimeCustomRegister` 注入对应能力，将抛出
  `InvalidArgumentException` 或 `UnsupportedRuntime`。
- `Request` / `Response` 没有转换成 PSR-7 对象，避免丢失原始请求引用；中间件中对原对象的修改（如 `withHeaders()`、
  `withCustomData()`）会直接体现在真实请求上。
- `EnvAttr::get()` 首次调用会从 `env.local.php` 或 `env.php` 自动加载并默认进入只读模式，如需在运行时修改请先
  `EnvAttr::changeSupportReadonly(false)`。
- `Route::clear()` 会清空 Webman 全局路由，仅在测试或 CLI 初始化时调用，线上环境慎用。
