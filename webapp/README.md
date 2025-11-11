# Webapp Monorepo

该目录使用 pnpm workspace 管理前端工具。当前仅包含 `apps/dto-generator` 一个应用，后续可以在 `apps/` 下继续扩展新的工具。

## 安装依赖

```bash
cd webapp
pnpm install
```

## 开发与构建

关于 `dto-generator` 的启动、构建与动态配置等详细说明，已迁移至 `apps/dto-generator/README.md`。如需为其他工具补充说明，可参照该结构新增各自的 README。

## 目录结构

```
webapp
├── apps                  # 各独立工具
│   └── dto-generator     # Vue + TS + Tailwind 的 DTO 生成工具
├── package.json          # workspace 脚手架脚本
├── pnpm-workspace.yaml   # workspace 配置
└── tsconfig.base.json    # 共享 tsconfig
```
