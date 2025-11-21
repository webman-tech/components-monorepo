# monorepo

webman tech 维护的组件的集合仓库，大部分组件优先适配 webman，但非 webman 下也可以使用。

## 安装

```bash
# 全部安装
composer require webman-tech/components-monorepo
# 按需安装举例
composer require webman-tech/amis-admin
composer require webman-tech/auth
# ...
```

## 使用

参考各个组件的文档

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

## 目录结构

- packages: 各个组件目录
- scripts: 辅助 monorepo 的一些常用脚本
- phpstan: phpstan 一些扩展和配置的文件
- src: 预留目录，暂时为空
- tests: 测试目录
    - Fixtures: 测试数据，按照各个组件的目录
    - Unit: 单元测试，按照各个组件的目录
    - webman: 用于单元测试的一个 webman 极小项目结构

# 新加包的流程

1. 在 src 下建立新的目录，可以复制 `packages/_template` 然后改下内容
2. 在 [.gitsplit.yml](.gitsplit.yml) 种添加新的拆包规则
3. 在 github 上新建空白项目
