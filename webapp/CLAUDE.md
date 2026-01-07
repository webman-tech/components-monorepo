# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

webapp 是一个基于 pnpm workspace 的前端工具 monorepo，用于管理项目的各种前端工具应用。

**当前包含的应用**：
- **dto-generator**：DTO 代码生成器（Vue 3 + TypeScript + Vite）

## 开发命令

### 安装依赖
```bash
cd webapp
pnpm install
```

### 开发调试
```bash
# 启动 dto-generator 开发服务器
cd webapp
pnpm dev

# 或使用 filter 启动特定应用
pnpm --filter dto-generator dev
```

### 构建
```bash
# 构建所有应用
cd webapp
pnpm build

# 构建特定应用
pnpm --filter dto-generator build
```

### 类型检查
```bash
cd webapp
pnpm --filter dto-generator lint
```

## 项目架构

### 目录结构
```
webapp/
├── apps/                    # 各独立前端工具
│   └── dto-generator/       # DTO 代码生成器
│       ├── src/             # 源代码
│       │   ├── components/   # Vue 组件
│       │   ├── lib/          # 工具库
│       │   ├── types/        # TypeScript 类型定义
│       │   ├── App.vue       # 根组件
│       │   └── main.ts       # 入口文件
│       ├── public/           # 静态资源
│       └── package.json     # 应用依赖
├── package.json             # workspace 脚手架配置
├── pnpm-workspace.yaml      # workspace 配置
├── tsconfig.base.json       # 共享 TypeScript 配置
└── README.md                # 项目说明
```

### dto-generator 应用
- **技术栈**：Vue 3 + TypeScript + Vite + Tailwind CSS + CodeMirror
- **构建输出**：单文件 HTML（内联所有 JS/CSS）
- **输出位置**：构建后写入 `packages/dto/web/`
- **功能**：从 JSON 数据生成 PHP DTO 代码

## 代码风格

- **Vue 3**：使用 Composition API
- **TypeScript**：严格类型检查
- **Tailwind CSS**：Utility-first CSS 框架
- **CodeMirror**：代码编辑器组件

## 开发流程

### dto-generator 构建流程

1. **开发阶段**：
   - 在 `webapp/apps/dto-generator/` 中开发
   - 使用 `pnpm dev` 启动开发服务器（支持 HMR）
   - 实时预览修改效果

2. **构建阶段**：
   - 运行 `pnpm build` 构建
   - Vite 将所有资源内联到单文件 HTML
   - 输出目录：`packages/dto/web/`
   - 输出文件：
     - `index.html`：主文件（包含所有 JS/CSS）
     - `favicon.svg`：图标

3. **使用阶段**：
   - `packages/dto/web/index.html` 可直接在浏览器打开
   - 或通过 PHP 路由返回（使用 `file_get_contents`）
   - 支持离线使用

### 添加新应用

1. 在 `apps/` 下创建新目录
2. 在 `pnpm-workspace.yaml` 中注册应用
3. 参照 `dto-generator` 的结构开发

## 注意事项

1. **pnpm workspace**：必须使用 pnpm 而非 npm 或 yarn
2. **构建输出**：构建会覆盖 `packages/dto/web/` 目录
3. **单文件输出**：所有资源内联到 HTML，无额外请求
4. **动态配置**：支持通过 URL 参数或全局变量动态配置
5. **TypeScript**：使用 `vue-tsc` 进行类型检查
6. **开发环境**：支持热模块替换（HMR）

## 相关文档

- dto-generator 详细文档：[apps/dto-generator/README.md](apps/dto-generator/README.md)
- DTO 组件文档：[packages/dto/CLAUDE.md](../packages/dto/CLAUDE.md)
