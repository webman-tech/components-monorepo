# Components Monorepo

webman tech 组件集合仓库，包含多个可独立使用的 PHP 组件。

## 安装

```bash
# 安装所有组件
composer require webman-tech/components-monorepo

# 按需安装单个组件
composer require webman-tech/amis-admin
composer require webman-tech/auth
# ...
```

## 组件列表

<!-- packages:start -->

- [webman-tech/amis-admin](https://github.com/webman-tech/amis-admin)
- [webman-tech/auth](https://github.com/webman-tech/auth)
- [webman-tech/common-utils](https://github.com/webman-tech/common-utils)
- [webman-tech/crontab-task](https://github.com/webman-tech/crontab-task)
- [webman-tech/debugbar](https://github.com/webman-tech/debugbar)
- [webman-tech/dto](https://github.com/webman-tech/dto)
- [webman-tech/log-reader](https://github.com/webman-tech/log-reader)
- [webman-tech/logger](https://github.com/webman-tech/logger)
- [webman-tech/swagger](https://github.com/webman-tech/swagger)

<!-- packages:end -->

## 使用说明

各组件的详细使用方法，请参考对应包的文档。

## LLM 支持

支持使用 [llm/skills](https://packagist.org/packages/llm/skills) 对当前仓库的 agent skill 进行主动发现

## 贡献指南

欢迎提交 Issue 和 Pull Request。

项目使用 Monorepo 结构管理多个组件，开发前请阅读 [CLAUDE.md](CLAUDE.md) 了解项目架构和开发规范。
