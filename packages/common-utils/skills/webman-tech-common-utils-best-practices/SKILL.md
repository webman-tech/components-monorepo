---
name: webman-tech-common-utils-best-practices
description: webman-tech/common-utils 最佳实践。使用场景：用户在 Webman/Laravel/自定义环境中使用统一 API、编写跨框架中间件、搭建测试环境时，给出明确的推荐写法。
---

# webman-tech/common-utils 最佳实践

## 核心原则

1. **只依赖公共 API**：使用 `Request/Response/Route/Container` 等抽象层，禁止直接引用具体框架类
2. **运行时感知**：组件会自动识别当前运行时，命令行和测试环境需要手动注册
3. **Helper 优先**：能用 `base_path()/config()/logger()` 等 Helper 函数就不要直接调用底层类

---

## 运行时与路径

### 在命令行/脚本中使用

命令行环境下运行时未自动识别，需手动注册：

```php
use WebmanTech\CommonUtils\Runtime;
use WebmanTech\CommonUtils\RuntimeCustomRegister;

RuntimeCustomRegister::register([
    'base_path' => '/path/to/project',
    'runtime_path' => '/path/to/project/runtime',
]);

// 之后可以直接使用 Helper
$path = base_path();
$logger = logger();
```

不要在命令行中直接使用 Webman 的原生路径函数，因为它们依赖 Webman 运行时上下文：

```php
// ❌ 直接调用 Webman 函数，命令行中可能不存在
$path = \support\App::runtimePath();

// ✅ 通过 RuntimeCustomRegister 注册后使用抽象层 Helper
$path = runtime_path();
```

### 判断当前运行时

```php
use WebmanTech\CommonUtils\Runtime;

if (Runtime::isWebman()) {
    // Webman 特有逻辑
}
if (Runtime::isCli()) {
    // 命令行逻辑
}
```

---

## HTTP 请求与响应

### 获取当前请求

始终通过 `Request::getCurrent()` 获取，不要直接依赖框架 Request 类：

```php
use WebmanTech\CommonUtils\Request;

$request = Request::getCurrent();
$all = $request->allPostJson();
$userId = $request->getCustomData('userId');
```

```php
// ❌ 直接使用框架类，无法在其他环境中运行
$request = request(); // Webman 专属函数

// ❌ 直接类型提示具体框架的 Request
public function handle(\Webman\Http\Request $request)
```

### 生成响应

```php
use WebmanTech\CommonUtils\Json;
use WebmanTech\CommonUtils\Response;

return Response::make()
    ->withStatus(201)
    ->withHeaders(['X-Request-Id' => $traceId])
    ->withBody(Json::encode(['id' => $userId]))
    ->getRaw();
```

---

## 跨框架中间件

继承 `BaseMiddleware` 实现的中间件可以在 Webman 和 Laravel 中复用：

```php
use WebmanTech\CommonUtils\Middleware\BaseMiddleware;
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Response;

final class TraceMiddleware extends BaseMiddleware
{
    protected function processRequest(Request $request, \Closure $handler): Response
    {
        $request->withHeaders(['X-Trace-Id' => uniqid('trace_', true)]);

        $response = $handler($request);

        return $response->withHeaders(['X-Response-Time' => microtime(true)]);
    }
}
```

关键点：对请求的修改（`withHeaders()`、`withCustomData()`）会直接写回底层真实请求对象，不需要额外传递。

不要在中间件中直接操作框架原生 Request：

```php
// ❌ 绕过抽象层，Webman 和 Laravel 行为不一致
$request->getRaw()->header('X-Foo');

// ✅ 通过抽象层操作
$request->header('X-Foo');
```

---

## JSON 序列化

推荐始终使用 `Json::encode()` 而非原生 `json_encode()`：

```php
use WebmanTech\CommonUtils\Json;

// 自动处理 INF/NAN、非 UTF-8、Expression 等边界情况
$json = Json::encode($data);
```

```php
// ❌ 原生 json_encode 遇到 NaN/非 UTF-8 会失败
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
```

当需要在前端模板中内联 JS 表达式时，使用 `Json\Expression`：

```php
use WebmanTech\CommonUtils\Json\Expression;

$data = [
    'callback' => new Expression('function() { alert(1); }'),
];
// callback 不会被引号包裹，直接输出为 JS 代码
```

---

## 环境变量（EnvAttr）

`EnvAttr` 首次调用 `get()` 后自动加载并进入只读模式：

```php
use WebmanTech\CommonUtils\EnvAttr;

$debug = EnvAttr::get('APP_DEBUG', false);
```

如需在运行时修改（仅限测试或初始化阶段）：

```php
// ✅ 先关闭只读，再修改
EnvAttr::changeSupportReadonly(false);
EnvAttr::set('APP_DEBUG', true);

// ❌ 不关闭只读直接修改，会抛异常
EnvAttr::set('APP_DEBUG', true);
```

---

## 缓存

### 单次请求内缓存用 ArrayCache

```php
use WebmanTech\CommonUtils\Cache\ArrayCache;

$cache = new ArrayCache(defaultTtl: 3600, maxTtl: 86400);
$cache->set('user:123', $userData);
$user = $cache->get('user:123');
```

### 禁用缓存用 NullCache

```php
use WebmanTech\CommonUtils\Cache\NullCache;
use Psr\SimpleCache\CacheInterface;

class MyService
{
    public function __construct(
        private CacheInterface $cache = new NullCache()
    ) {}

    public function getData(string $key): mixed
    {
        return $this->cache->get($key) ?? $this->expensiveOperation($key);
    }
}
```

---

## 测试环境搭建

### 注册测试运行时

在测试入口文件中调用 `Factory::registerTestRuntime()`：

```php
// tests/bootstrap.php
use WebmanTech\CommonUtils\Testing\Factory;

Factory::registerTestRuntime(__DIR__ . '/test-demo');
```

之后所有公共 API 自动使用测试假体，无需关心框架差异。

### 在测试中使用

```php
use WebmanTech\CommonUtils\Request;
use WebmanTech\CommonUtils\Route;

test('example', function () {
    $request = Request::getCurrent(); // 返回 TestRequest
    $route = Route::getCurrent();     // 返回 TestRoute

    // 业务测试...

    // 清理，避免污染下一个测试
    Request::getCurrent()->clear();
    Route::getCurrent()->clear();
});
```

`Route::clear()` 会清空全局路由，**仅在测试或 CLI 初始化时调用**，线上环境禁用。

---

## 常见错误

| 错误 | 原因 | 解决 |
|------|------|------|
| `InvalidArgumentException` / `UnsupportedRuntime` | 运行时不可识别且未注册自定义实现 | 调用 `RuntimeCustomRegister::register()` 注册 |
| `EnvAttr` 修改报错 | 默认只读模式已开启 | 先调用 `EnvAttr::changeSupportReadonly(false)` |
| 测试间数据污染 | TestRequest/TestRoute 未清理 | 在 `beforeEach` 中调用 `clear()` |
| `json_encode` 返回 `false` | 数据含 NaN/INF/非 UTF-8 | 改用 `Json::encode()` |
| `Route::clear()` 导致线上路由丢失 | 在生产代码中调用了 clear | 仅在测试中使用 |
