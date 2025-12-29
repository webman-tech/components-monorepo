# 从 JSON 文件快速渲染页面

当你在 amis 官方 editor 里搭好页面后，可以把导出的 JSON 文件直接放进项目里，由 amis-admin 自动渲染为页面，从而快速产出“特定界面”（非 CRUD 场景也适用）。

## 1. 放置 json 文件

默认目录为：

`resource/amis-json/{name}.json`

例如：

`resource/amis-json/dashboard.json`

## 2. 开启路由

在 `config/plugin/webman-tech/amis-admin/amis.php` 中开启：

```php
'json_page' => [
    'route' => [
        'enable' => true,
        'group' => '/amis-json',
        'middleware' => [
            // 建议加上你的登录/权限中间件
        ],
    ],
],
```

访问：`/amis-json/dashboard`

同一个地址支持两种模式：

- 页面预览：`GET /amis-json/dashboard`
- schemaApi：`GET /amis-json/dashboard?_ajax=1`（返回 `amis_response` 格式）

## 3. 变量替换（占位符）

amis-admin 会递归替换 JSON 中的所有字符串，支持以下占位符：

### 3.1 自定义变量

在配置里返回变量：

```php
'json_page' => [
    'vars' => function ($request) {
        return [
            'login_api' => route('admin.login'),
        ];
    },
],
```

在 JSON 中使用：

```json
{
  "api": "{{login_api}}"
}
```

### 3.2 内置表达式

- `{{route:xxx}}`：`route('xxx')`
- `{{config:xxx}}`：`config('xxx')`
- `{{env:xxx}}`：读取 env

示例：

```json
{
  "title": "{{config:app.name}}",
  "api": "{{route:admin.login}}"
}
```

