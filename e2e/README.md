# E2E 测试（真实框架环境验证）

与根仓库的单测（custom runtime 假体）互补，e2e 在**真实的 webman / laravel 官方骨架**中验证各组件的实际行为：

- **真实插件安装链路**：webman 的 `post-package-install → support\Plugin::install → 包 Install.php` 全链路（而非单测中的手工模拟）
- **真实依赖声明完整性**：e2e 应用仅 require 被测包，包的 composer.json 若缺少依赖会直接安装失败
- **真实运行时分支**：webman 与 laravel 各自的全局 helpers、容器、中间件管线、validator 实现
- **真实进程 HTTP 测试**：webman 侧启动真实进程（随机端口 + /health 探活）发起 HTTP 请求断言

## 目录结构

```
e2e/
├── setup.php                 # 安装命令（create-project → patch → update → reinstall → sync）
├── README.md
├── webman-src/               # 提交：自有代码（controller/config 覆盖/tests）
└── laravel-src/              # 提交：自有代码（routes/bootstrap 覆盖/tests）
# 生成物（.gitignore 忽略，可抛弃）：e2e/webman、e2e/laravel
```

## 命令

```bash
# 完整安装 webman e2e 应用（删除重建）
php e2e/setup.php webman

# 完整安装 laravel e2e 应用
php e2e/setup.php laravel

# 全部安装
php e2e/setup.php all

# 仅同步自有代码（dev 快速迭代）
php e2e/setup.php webman --sync
php e2e/setup.php laravel --sync

# 运行测试
cd e2e/webman && vendor/bin/pest
cd e2e/laravel && vendor/bin/pest
```

## 安装流程（顺序关键）

1. `composer create-project` 官方骨架（workerman/webman、laravel/laravel ^12）
2. patch composer.json：path repository（`../../packages/*`，symlink + versions 钉 dev-main）+ 组件依赖
3. `composer update`（COMPOSER_ROOT_VERSION=dev-main 兜底 detached HEAD 的版本解析）
4. `composer reinstall` 本地包（webman：批量 update 时 composer 进程内 autoloader 未就绪，
   包内 Install.php 不触发；单包 reinstall 会走真实安装链落地 `config/plugin/webman-tech/*`）
5. copy `*-src/` 自有代码到应用目录（覆盖式，保证自有 config 覆盖在插件默认配置之后生效）

## 官方骨架升级

生成目录是可抛弃的：**删除 `e2e/webman`（或 `e2e/laravel`）后重跑 `php e2e/setup.php <app>` 即可**。
自有代码全部在 `*-src/` 中提交，不 fork 任何骨架文件，升级零成本。

## dev 迭代

- 改 `packages/*/src`：symlink 使改动即时生效，直接重跑测试
- 改 `e2e/*-src`：先 `php e2e/setup.php <app> --sync` 再跑测试
- e2e 测试不使用 Pest snapshot（可抛弃 app 重建后 snapshot 会丢失），
  一律用 `*-src` 内提交的 fixture（见 `webman-src/tests/fixtures/openapi.json`）或内联断言

## webman e2e 覆盖点

| 测试文件 | 验证内容 |
|----------|----------|
| ServerBootTest | 进程启动 /health 探活、未注册路由 404 |
| PluginInstallTest | Install.php 落地的 `config/plugin/webman-tech/*` 守护、swagger 插件路由注册 |
| CommonUtilsRequestTest | Request/Session/Response facade 真实 HTTP 下的 GET/POST json/form/header/session/traceId |
| SwaggerTest | `/openapi/doc` 输出结构与提交 fixture 比对 |
| LoggerMiddlewareTest | HttpRequestLogMiddleware 写 `runtime/logs/httpRequest/` 日志、RequestTraceProcessor 的 traceId |
| AuthTest | tinywan/jwt 登录签发、Authentication 中间件有效/无/无效 token 的 200/401 |
| DtoControllerTest | laravel-monorepo validator() 下的 DTO 验证成功 200 / 失败 422 结构 |

## laravel e2e 覆盖点

| 测试文件 | 验证内容 |
|----------|----------|
| RuntimeDetectTest | Runtime 自动检测：isLaravel() true、isWebman()/isCustom() false |
| CommonUtilsFacadeTest | Request（\request() 分支）/Session/Response（Symfony）/Container/Log facade、Runtime::terminating()、路径函数映射 |
| DtoValidationTest | 框架自带 validator() 下的 DTO 验证成功 / 422 结构 |
| LoggerMiddlewareTest | BaseMiddleware::handle() laravel 入口、custom driver 桥接的 httpRequest channel 日志与 traceId |

## CI

`.github/workflows/e2e.yml`：e2e-webman / e2e-laravel 两个 job（PHP 8.4 + pcntl/posix/pdo_sqlite），不缓存生成 app。
