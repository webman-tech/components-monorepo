# DTO Generator

一个基于 Vite + Vue 3 + TypeScript + Tailwind + CodeMirror 的 DTO 生成工具，最终构建为单文件 HTML，以便在 PHP 路由中通过 `file_get_contents` 等方式输出使用。

## 开发调试

```bash
cd webapp
pnpm dev
```

默认仅启动 `dto-generator` 的 Vite 开发服务器，支持 HMR 与 Tailwind 开发体验。如果需要在 monorepo 中新增其他应用，可通过 `pnpm --filter <app> dev` 的方式分别启动。

## 构建

```bash
cd webapp
pnpm build
```

构建完成后会将已经内联完 JS/CSS 的单文件前端写入仓库根目录的 `packages/dto/web`，目录结构如下：

```
packages/dto/web
├── favicon.svg
└── index.html
```

`index.html` 可以直接双击离线打开，也可以作为静态内容通过 PHP 路由返回。

## 动态配置

前端会从以下渠道读取默认配置，优先级为 **URL 查询参数 > 全局变量 > 内置默认值**：

| 配置项 | 说明 | 默认值 | 示例 |
| --- | --- | --- | --- |
| `defaultGenerationType` | 默认选中的生成类型 | `dto` | `?defaultGenerationType=form` |
| `defaultNamespace` | DTO 类名命名空间前缀 | `App\DTO` | `?defaultNamespace=App%5CAdmin%5CDTO` |

当你在 PHP 中通过 `file_get_contents` 输出该页面时，也可以在返回的 HTML 中注入以下脚本来设置全局配置：

```html
<script>
  window.__DTO_GENERATOR_CONFIG = {
    defaultGenerationType: 'form',
    defaultNamespace: 'App\\Admin\\DTO'
  };
</script>
```

这样就可以针对不同项目动态调整默认生成类型和 DTO 命名空间。需要更多配置项时，可在 `apps/dto-generator/src/App.vue` 中扩展解析逻辑。
