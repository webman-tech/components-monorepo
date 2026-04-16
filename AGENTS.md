## 项目概述

这是一个 webman tech 组件的 monorepo，包含多个 PHP 组件包。虽然优先适配 webman 框架，但组件也可以在非 webman 环境中使用。

### 组件列表

| 包名 | 详细文档 |
|------|----------|
| amis-admin | [packages/amis-admin/AGENTS.md](packages/amis-admin/AGENTS.md) |
| auth | [packages/auth/AGENTS.md](packages/auth/AGENTS.md) |
| common-utils | [packages/common-utils/AGENTS.md](packages/common-utils/AGENTS.md) |
| crontab-task | [packages/crontab-task/AGENTS.md](packages/crontab-task/AGENTS.md) |
| debugbar | [packages/debugbar/AGENTS.md](packages/debugbar/AGENTS.md) |
| dto | [packages/dto/AGENTS.md](packages/dto/AGENTS.md) |
| log-reader | [packages/log-reader/AGENTS.md](packages/log-reader/AGENTS.md) |
| logger | [packages/logger/AGENTS.md](packages/logger/AGENTS.md) |
| swagger | [packages/swagger/AGENTS.md](packages/swagger/AGENTS.md) |

### 前端工具

| 工具名 | 详细文档 |
|--------|----------|
| dto-generator | [webapp/AGENTS.md](webapp/AGENTS.md) |

## 开发命令

### 测试
```bash
# 运行所有测试
composer test
# 或直接使用 pest
vendor/bin/pest

# 运行单个测试文件
vendor/bin/pest tests/Unit/DTO/SomeTest.php

# 运行特定测试目录下的所有测试
vendor/bin/pest tests/Unit/DTO

# 更新测试快照
vendor/bin/pest --update-snapshots
```

### 静态分析
```bash
# 运行 PHPStan 静态分析（level 9）
composer phpstan
# 或
vendor/bin/phpstan

# 使用原始输出格式（便于脚本处理）
composer phpatan-raw
```

### 代码重构
```bash
# 运行 Rector 进行代码重构/升级
composer rector
# 或
vendor/bin/rector
```

### 前端工具开发
```bash
# 进入 webapp 目录
cd webapp

# 安装依赖
pnpm install

# 启动开发服务器
pnpm dev

# 构建前端工具
pnpm build

# 类型检查
pnpm --filter dto-generator lint
```

### Monorepo 维护脚本
```bash
# 更新子包相关的信息
composer script:update-packages
```

## 项目架构

### Monorepo 结构
- **packages/**: 各个组件的源代码目录，每个子包都是独立的 composer 包
- **webapp/**: 前端工具 monorepo（使用 pnpm workspace）
- **tests/**: 测试目录
  - Unit: 单元测试，按组件名划分目录（如 Unit/DTO、Unit/Logger 等）
  - Fixtures: 测试数据
  - Pest.php: Pest 测试框架配置文件
  - bootstrap.php: 测试启动文件
- **phpstan/**: PHPStan 静态分析工具的扩展和配置
  - stubs/: 自定义类型声明存根（如 OpenApi、Webman 等）
- **scripts/**: Monorepo 维护脚本
- **.gitsplit.yml**: Git Split 配置文件

### 组件依赖关系
- `common-utils` 是基础工具库，被多个其他包依赖
- `dto` 依赖 `common-utils`
- 根 composer.json 使用 `replace` 字段将所有子包作为"虚拟包"

### Git Split 机制
项目使用 [gitsplit](https://github.com/jderusse/docker-gitsplit) 将 monorepo 拆分为多个独立的 git 仓库：
- 每个子包对应一个独立的 GitHub 仓库（如 `webman-tech/dto`）
- 配置文件：`.gitsplit.yml`
- 只有 `main` 分支和版本标签（`v*.*.*`）会被拆分

### Composer 自动加载
- 根 composer.json 的 autoload 是通过 `scripts/generate_composer.php` 自动生成的
- 修改子包的 composer.json 后需要运行 `composer script:update-packages` 来更新根 composer.json
- 所有子包的命名空间都遵循 `WebmanTech\<ComponentName>\` 模式

### 前端工具构建流程
1. 在 `webapp/apps/dto-generator/` 中开发
2. 使用 `pnpm build` 构建
3. 输出单文件 HTML 到 `packages/dto/web/`
4. 可直接在浏览器中打开或通过 PHP 路由返回

## 代码风格

- PHP 8.2+ 语法（CI 矩阵覆盖 PHP 8.2/8.3/8.4）
- 4 空格缩进
- 使用 declare(strict_types=1) 严格类型
- PHPStan Level 9 静态分析（配置文件：`phpstan.dist.neon`）
- 使用 Pest 进行测试（而非 PHPUnit）

前端工具：
- Vue 3（Composition API）
- TypeScript
- Tailwind CSS

## 添加新组件流程

当需要添加新组件时，按以下步骤操作：

1. **创建包目录**：在 `packages` 下建立新目录（可复制 `packages/_template` 作为模板）
2. **配置拆包规则**：在 `.gitsplit.yml` 中添加拆包规则
3. **创建 GitHub 仓库**：在 GitHub 上新建对应的空白项目
4. **更新文档**：
   - 在根目录的 README.md 中添加组件说明
   - 创建该包的 AGENTS.md 文档
5. **更新依赖**：运行 `composer script:update-packages` 更新根 composer.json

## 测试相关

### Snapshot 测试
项目使用 Pest 的 snapshot 功能进行输出测试：
```bash
# 更新所有失败的 snapshot
vendor/bin/pest --update-snapshots

# 只更新特定测试的 snapshot
vendor/bin/pest tests/Unit/Swagger/Controller/OpenapiControllerTest.php --update-snapshots
```

### 测试组织结构
- `tests/Unit/<ComponentName>/`: 按组件组织的单元测试
- `tests/Fixtures/`: 测试数据和示例类
- `tests/.pest/snapshots/`: snapshot 文件存储目录

### 测试环境
- `tests/bootstrap.php` 通过 `Factory::registerTestRuntime()` 注册测试运行时，基础目录为 `tests/test-demo`
- 测试使用自定义的 TestContainer，每次测试前会自动清理（`Pest.php` 中的 `beforeEach`）
- Context 会在每次测试后自动 reset，避免污染

### 测试 Helper 函数（定义在 `tests/Pest.php`）
- `fixture_get_path($path)` / `fixture_get_content($path)` / `fixture_get_require($path)`：访问 `tests/Fixtures/` 下的测试数据
- `request_create_one()`：创建一个干净的测试 Request 实例
- `request_get_raw($request)`：获取 Request 的底层 TestRequest 对象

## 注意事项

1. **Monorepo 维护**：
   - 修改子包的 composer.json 后，记得运行 `composer script:update-packages` 更新根 composer.json
   - PHPStan 会排除 `packages/*/src/Install.php` 文件（通常是 webman 的安装配置脚本）
   - **README.md 中的 `<!-- packages:start -->` 和 `<!-- packages:end -->` 区域由 `scripts/generate_readme.php` 自动生成，请勿手动修改**

2. **测试相关**：
   - 修改代码后，记得更新对应的 snapshot 文件
   - 使用 `--update-snapshots` 选项更新快照

3. **前端工具**：
   - 构建前端工具会覆盖 `packages/dto/web/` 目录
   - 修改 dto-generator 源代码后需要重新构建
   - 详见 [webapp/AGENTS.md](webapp/AGENTS.md)

4. **组件开发**：
   - 各个组件的详细开发规范和架构说明，请查看对应包目录下的 AGENTS.md 文件
   - 优先参考现有组件的实现模式
