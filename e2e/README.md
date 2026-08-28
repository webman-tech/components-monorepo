# E2E 测试（真实框架环境验证）

与根仓库的单测（custom runtime 假体）互补，e2e 在**真实的 webman / laravel 官方骨架**中验证各组件的实际行为：

- **真实插件安装链路**：webman 的 `post-package-install → support\Plugin::install → 包 Install.php` 全链路（而非单测中的手工模拟）
- **真实依赖声明完整性**：e2e 应用仅 require 被测包，包的 composer.json 若缺少依赖会直接安装失败
- **真实运行时分支**：webman 与 laravel 各自的全局 helpers、容器、中间件管线、validator 实现
- **真实进程 HTTP 测试**：webman 侧基于 [webman-tech/testing](https://github.com/webman-tech/testing) 组件启动真实进程 发起 HTTP 请求断言

## 目录结构

```
e2e/
├── e2e-setup.php             # 应用定义（安装编排由 webman-tech/testing 的 e2e-setup 框架执行）
├── README.md
├── webman-src/               # 提交：自有代码（controller/config 覆盖/tests）
└── laravel-src/              # 提交：自有代码（routes/bootstrap 覆盖/tests）
# 生成物（.gitignore 忽略，可抛弃）：e2e/webman、e2e/laravel
```

## 命令

```bash
# 完整安装 e2e 应用（删除重建）
vendor/bin/e2e-setup install webman
vendor/bin/e2e-setup install laravel

# 仅同步自有代码（dev 快速迭代）
vendor/bin/e2e-setup sync webman
vendor/bin/e2e-setup sync laravel

# 被测包经 GitHub VCS dev-main 安装（发布链路验证，需先推送 main）
vendor/bin/e2e-setup install webman --vcs

# 运行测试
composer e2e:test:webman
composer e2e:test:laravel
```

以上命令均有根 composer scripts 封装：`e2e:install:webman|laravel|all`、`e2e:sync`、`e2e:vcs`、`e2e:test:webman|laravel|all`。

## 安装流程（顺序关键）

1. `composer create-project` 官方骨架（workerman/webman、laravel/laravel ^12）
2. patch composer.json：被测包声明转 path repository（同 path 合并，symlink + versions 钉 dev-main）+ 组件依赖
3. `composer update`（COMPOSER_ROOT_VERSION=dev-main 兜底 detached HEAD 的版本解析）
4. `composer reinstall` 本地包与 webman/console（webman：批量 update 时 composer 进程内 autoloader 未就绪，
   包内 Install.php 不触发；单包 reinstall 会走真实安装链落地 `config/plugin/webman-tech/*` 与 `webman` CLI 入口）
5. copy `*-src/` 自有代码到应用目录（覆盖式，保证自有 config 覆盖在插件默认配置之后生效）

## 官方骨架升级

生成目录是可抛弃的：**删除 `e2e/webman`（或 `e2e/laravel`）后重跑 `vendor/bin/e2e-setup install <app>` 即可**。
自有代码全部在 `*-src/` 中提交，不 fork 任何骨架文件，升级零成本。

## webman e2e 覆盖点

| 测试文件 | 验证内容 |
|----------|----------|
| Feature/ServerBootTest | 进程启动 /health 探活、未注册路由 404 |
| Feature/PluginInstallTest | Install.php 落地的 `config/plugin/webman-tech/*` 守护、swagger 插件路由注册 |
| Feature/CommonUtilsRequestTest | Request/Session/Response facade 真实 HTTP 下的 GET/POST json/form/header/session/traceId |
| Feature/SwaggerTest | `/openapi/doc` 输出结构与提交 fixture 比对（含 Eloquent Model 自动展开的 AmisUser schema） |
| Feature/LoggerMiddlewareTest | HttpRequestLogMiddleware 写 `runtime/logs/httpRequest/` 日志、RequestTraceProcessor 的 traceId |
| Feature/AuthTest | tinywan/jwt 登录签发、Authentication 中间件有效/无/无效 token 的 200/401 |
| Feature/DtoControllerTest | laravel-monorepo validator() 下的 DTO 验证成功 200 / 失败 422 结构 |
| Feature/CrontabTaskTest | 插件 process 配置加载、TaskProcess 进程随 server 启动、cron 真实调度执行、LogTrait 日志写入 channel、CLI 命令（list / exec） |
| Feature/AmisAdminTest | AmisSourceController CRUD（页面 schema / _ajax 分页列表 / 搜索 / 详情 / 新增 / 编辑 / 删除）、LaravelValidator 验证失败 422 响应结构、amis 插件配置覆盖（validator 桥接）、异常 handler 转换 |

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
