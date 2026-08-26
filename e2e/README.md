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
4. `composer reinstall` 本地包与 webman/console（webman：批量 update 时 composer 进程内 autoloader 未就绪，
   包内 Install.php 不触发；单包 reinstall 会走真实安装链落地 `config/plugin/webman-tech/*` 与 `webman` CLI 入口）
5. copy `*-src/` 自有代码到应用目录（覆盖式，保证自有 config 覆盖在插件默认配置之后生效）

## 官方骨架升级

生成目录是可抛弃的：**删除 `e2e/webman`（或 `e2e/laravel`）后重跑 `php e2e/setup.php <app>` 即可**。
自有代码全部在 `*-src/` 中提交，不 fork 任何骨架文件，升级零成本。

## dev 迭代

- 改 `packages/*/src`：symlink 使改动即时生效，直接重跑测试
- 改 `e2e/*-src`：先 `php e2e/setup.php <app> --sync` 再跑测试
- e2e 测试不使用 Pest snapshot（可抛弃 app 重建后 snapshot 会丢失），
  一律用 `*-src` 内提交的 fixture（见 `webman-src/tests/fixtures/openapi.json`）或内联断言
- 更新 SwaggerTest fixture：从运行中的应用取实际输出（`curl .../openapi/doc`），
  保存到 `webman-src/tests/fixtures/openapi.json`（源目录）；直接保存到生成目录会被下次重建覆盖
- 涉及 crontab-task 调度时序的断言必须覆盖跨分钟边界：workerman/crontab 按整分钟对齐调度
  （`new Crontab()` 后等到下一个 xx:00 才首次触发），见 `e2e_crontab_task_wait_executed()`；
  同一执行内日志落盘顺序为 start → 副作用行 → end，断言日志时需轮询等待（见 CrontabTaskTest）
- WebmanServer 在启停时按 cwd 扫尾清理本应用目录的残留 workerman 进程：
  master 异常死亡后 worker 会成为孤儿进程继续跑定时任务（TaskProcess 每秒写副作用文件），
  会污染“等待副作用增长”类的时序断言

## webman e2e 覆盖点

| 测试文件 | 验证内容 |
|----------|----------|
| ServerBootTest | 进程启动 /health 探活、未注册路由 404 |
| PluginInstallTest | Install.php 落地的 `config/plugin/webman-tech/*` 守护、swagger 插件路由注册 |
| CommonUtilsRequestTest | Request/Session/Response facade 真实 HTTP 下的 GET/POST json/form/header/session/traceId |
| SwaggerTest | `/openapi/doc` 输出结构与提交 fixture 比对（含 Eloquent Model 自动展开的 AmisUser schema） |
| LoggerMiddlewareTest | HttpRequestLogMiddleware 写 `runtime/logs/httpRequest/` 日志、RequestTraceProcessor 的 traceId |
| AuthTest | tinywan/jwt 登录签发、Authentication 中间件有效/无/无效 token 的 200/401 |
| DtoControllerTest | laravel-monorepo validator() 下的 DTO 验证成功 200 / 失败 422 结构 |
| CrontabTaskTest | 插件 process 配置加载、TaskProcess 进程随 server 启动、cron 真实调度执行、LogTrait 日志写入 channel、CLI 命令（list / exec） |
| AmisAdminTest | AmisSourceController CRUD（页面 schema / _ajax 分页列表 / 搜索 / 详情 / 新增 / 编辑 / 删除）、LaravelValidator 验证失败 422 响应结构、amis 插件配置覆盖（validator 桥接）、异常 handler 转换 |

## laravel e2e 覆盖点

| 测试文件 | 验证内容 |
|----------|----------|
| RuntimeDetectTest | Runtime 自动检测：isLaravel() true、isWebman()/isCustom() false |
| CommonUtilsFacadeTest | Request（\request() 分支）/Session/Response（Symfony）/Container/Log facade、Runtime::terminating()、路径函数映射 |
| DtoValidationTest | 框架自带 validator() 下的 DTO 验证成功 / 422 结构 |
| LoggerMiddlewareTest | BaseMiddleware::handle() laravel 入口、custom driver 桥接的 httpRequest channel 日志与 traceId |

## 不纳入 e2e 的组件

| 组件 | 原因 |
|------|------|
| debugbar | 收益成本比低：可选依赖探测分支虽多但均为运行时 class_exists 探测，且该组件后续可能弃用，不投入 e2e |

## CI

`.github/workflows/e2e.yml`：e2e-webman / e2e-laravel 两个 job（PHP 8.4 + pcntl/posix/pdo_sqlite），不缓存生成 app。
