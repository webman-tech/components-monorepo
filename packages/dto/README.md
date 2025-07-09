# webman-tech/dto

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
出来的，请勿直接修改

> 简介

各种常用的 DTO

- BaseDTO: 基础 DTO
    - 支持 `fromData` 从数组创建对象，并自动根据类型和 [ValidationRules](./src/Attributes/ValidationRules.php) 进行验证
    - 支持 `toArray`，自动将 public 属性转出为数组
- BaseRequestDTO: 基础请求的 DTO
    - 支持 `fromRequest` 从各种框架的 Request 来创建对象
- BaseResponseDTO: 基础响应的 DTO
    - 支持 `toResponse`