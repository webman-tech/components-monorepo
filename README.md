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

- [webman-tech/amis-admin](./packages/amis-admin/README.md)
- [webman-tech/auth](./packages/auth/README.md)
- [webman-tech/crontab-task](./packages/crontab-task/README.md)
- [webman-tech/debugbar](./packages/debugbar/README.md)
- [webman-tech/dto](./packages/dto/README.md)
- [webman-tech/log-reader](./packages/log-reader/README.md)
- [webman-tech/logger](./packages/logger/README.md)
- [webman-tech/swagger](./packages/swagger/README.md)

<!-- packages:end -->

# 新加包的流程

1. 在 src 下建立新的目录，可以复制 `packages/_template` 然后改下内容
2. 在 [.gitsplit.yml](.gitsplit.yml) 种添加新的拆包规则
3. 在 github 上新建空白项目
