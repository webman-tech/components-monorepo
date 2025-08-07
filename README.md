# monorepo

webman tech 维护的组件的集合仓库

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
- [webman-tech/crontab-task](https://github.com/webman-tech/crontab-task)
- [webman-tech/debugbar](https://github.com/webman-tech/debugbar)
- [webman-tech/dto](https://github.com/webman-tech/dto)
- [webman-tech/log-reader](https://github.com/webman-tech/log-reader)
- [webman-tech/logger](https://github.com/webman-tech/logger)
- [webman-tech/swagger](https://github.com/webman-tech/swagger)

<!-- packages:end -->

# 新加包的流程

1. 在 src 下建立新的目录，可以复制 `packages/_template` 然后改下内容
2. 在 [.gitsplit.yml](.gitsplit.yml) 种添加新的拆包规则
3. 在 github 上新建空白项目
