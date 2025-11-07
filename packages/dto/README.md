# webman-tech/dto

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
出来的，请勿直接修改

## 简介

webman 数据传输对象（DTO）组件，提供一套完整的数据处理解决方案，包括数据验证、类型转换、请求处理、响应生成等功能。

该组件通过属性注解和反射机制，实现了自动化的数据处理流程，解决了手动处理数据时容易出错、代码重复等问题。

## 功能特性

- **自动数据验证**：基于属性注解的验证规则，支持多种验证类型
- **类型自动转换**：支持基本类型、枚举、日期时间、嵌套对象等类型的自动转换
- **请求数据处理**：从 HTTP 请求中自动提取和处理数据
- **响应数据生成**：将对象转换为数组或 HTTP 响应
- **配置数据处理**：专门处理应用配置数据
- **灵活的序列化控制**：通过注解控制对象到数组的转换行为
- **丰富的属性注解**：提供多种内置注解满足不同场景需求

## 安装

```bash
composer require webman-tech/dto
```

## 快速开始

### 1. 创建基础 DTO

```php
<?php

use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Attributes\ValidationRules;

class UserDTO extends BaseDTO
{
    #[ValidationRules(required: true, string: true, maxLength: 50)]
    public string $name;
    
    #[ValidationRules(integer: true, min: 0, max: 150)]
    public int $age;
    
    #[ValidationRules(enum: UserStatus::class)]
    public UserStatus $status;
}
```

### 2. 使用 DTO

```php
// 从数组创建 DTO 实例
$userDTO = UserDTO::fromData([
    'name' => 'John',
    'age' => 30,
    'status' => UserStatus::Active
]);

// 转换为数组
$userData = $userDTO->toArray();
```

## 核心组件

### BaseDTO 基础数据传输对象

[BaseDTO](src/BaseDTO.php) 是所有 DTO 的基类，提供核心功能：

- `fromData()`: 从数组创建 DTO 实例
- `toArray()`: 将 DTO 转换为数组
- 数据验证和类型转换
- 支持自定义验证规则

### BaseRequestDTO 请求数据传输对象

[BaseRequestDTO](src/BaseRequestDTO.php) 用于处理 HTTP 请求数据：

- `fromRequest()`: 从 HTTP 请求创建 DTO 实例
- 支持多种请求数据来源（Query、Form、JSON 等）
- 自动提取请求数据

### BaseResponseDTO 响应数据传输对象

[BaseResponseDTO](src/BaseResponseDTO.php) 用于生成 HTTP 响应：

- `toResponse()`: 生成 HTTP 响应
- 支持自定义响应头和状态码
- 可配置响应格式

### BaseConfigDTO 配置数据传输对象

[BaseConfigDTO](src/BaseConfigDTO.php) 用于处理应用配置：

- `fromConfig()`: 从配置创建 DTO 实例
- 支持配置合并和默认值
- 适用于复杂的配置场景

## 属性注解

### ValidationRules 验证规则

[ValidationRules](src/Attributes/ValidationRules.php) 是核心的验证注解，支持多种验证选项：

```php
use WebmanTech\DTO\Attributes\ValidationRules;

class ExampleDTO extends BaseDTO
{
    // 基本类型验证
    #[ValidationRules(required: true, string: true)]
    public string $name;
    
    // 数值验证
    #[ValidationRules(integer: true, min: 1, max: 100)]
    public int $age;
    
    // 枚举验证
    #[ValidationRules(enum: StatusEnum::class)]
    public StatusEnum $status;
    
    // 数组验证
    #[ValidationRules(array: true, arrayItem: ItemDTO::class)]
    public array $items;
    
    // 嵌套对象验证
    #[ValidationRules(object: AddressDTO::class)]
    public AddressDTO $address;
    
    // 自定义规则
    #[ValidationRules(rules: 'email|unique:users')]
    public string $email;
}
```

### ToArrayConfig 数组转换配置

[ToArrayConfig](src/Attributes/ToArrayConfig.php) 用于控制 DTO 转换为数组的行为：

```php
use WebmanTech\DTO\Attributes\ToArrayConfig;

#[ToArrayConfig(
    only: ['name', 'age'],           // 仅包含指定字段
    exclude: ['password'],           // 排除指定字段
    ignoreNull: true,                // 忽略空值
    emptyArrayAsObject: ['items'],   // 空数组转为空对象
    singleKey: 'name',               // 将单个 key 的值作为 toArray 的结果
)]
class UserResponseDTO extends BaseResponseDTO
{
    public string $name;
    public int $age;
    public ?string $password = null;
    public array $items = [];
}
```

### RequestPropertyIn 请求属性来源

用于指定请求中属性的数据来源：

- [RequestPropertyInQuery](src/Attributes/RequestPropertyInQuery.php): Query 参数
- [RequestPropertyInForm](src/Attributes/RequestPropertyInForm.php): Form 数据
- [RequestPropertyInJson](src/Attributes/RequestPropertyInJson.php): JSON 数据
- [RequestPropertyInHeader](src/Attributes/RequestPropertyInHeader.php): Header 数据
- [RequestPropertyInCookie](src/Attributes/RequestPropertyInCookie.php): Cookie 数据

```php
use WebmanTech\DTO\Attributes\RequestPropertyInQuery;
use WebmanTech\DTO\Attributes\RequestPropertyInHeader;

class ApiRequestDTO extends BaseRequestDTO
{
    #[RequestPropertyInQuery]
    public string $page;
    
    #[RequestPropertyInHeader(name: 'X-API-Token')]
    public string $apiToken;
}
```

## 使用指南

### 数据验证

DTO 组件提供强大的数据验证功能：

```php
class UserCreateDTO extends BaseDTO
{
    #[ValidationRules(minLength: 2, maxLength: 50)]
    public string $name;
    
    #[ValidationRules(min: 18, max: 100)]
    public int $age;
    
    #[ValidationRules(rules: 'email')]
    public string $email;
    
    #[ValidationRules(enum: UserRole::class)]
    public UserRole $role;
    
    // 自定义验证规则
    protected static function getExtraValidationRules(): array
    {
        return [
            'email' => 'unique:users,email'
        ];
    }
    
    // 自定义错误消息
    protected static function getValidationRuleMessages(): array
    {
        return [
            'name.required' => '姓名不能为空',
            'age.min' => '年龄不能小于18岁'
        ];
    }
}
```

注意：DTO 会自动根据当前属性的类型和是否有默认值来设置必填和数据类型验证

### 类型转换

支持多种类型的自动转换：

```php
use WebmanTech\DTO\BaseDTO;

class ProductDTO extends BaseDTO
{
    // 基本类型转换
    public int $id;
    
    // 枚举类型转换
    public ProductStatus $status;
    
    // 日期时间转换
    public DateTime $createdAt;
    
    // 嵌套对象转换
    public CategoryDTO $category;
    
    // 数组项转换
    #[ValidationRules(arrayItem: TagDTO::class)]
    public array $tags;
}
```

### 请求处理

从 HTTP 请求中自动提取数据：

```php
use WebmanTech\DTO\BaseRequestDTO;

class UserCreateRequest extends BaseRequestDTO
{
    #[RequestPropertyInJson(required: true)]
    public string $name;
    
    #[RequestPropertyInJson]
    public int $age = 0;
    
    #[RequestPropertyInHeader(name: 'X-Request-ID')]
    public string $requestId;
}

// 在控制器中使用
public function create(Request $request)
{
    $dto = UserCreateRequest::fromRequest($request);
    // 处理业务逻辑
}
```

### 响应生成

生成结构化的响应数据：

```php
use WebmanTech\DTO\BaseResponseDTO;

class UserResponse extends BaseResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// 在控制器中使用
public function show(int $id)
{
    $user = User::find($id);
    $response = new UserResponse($user->id, $user->name, $user->age);
    return $response->toResponse();
}
```

### 配置处理

处理应用配置数据：

```php
use WebmanTech\DTO\BaseConfigDTO;

class DatabaseConfig extends BaseConfigDTO
{
    public function __construct(
        public string $host = 'localhost',
        public int $port = 3306,
        public string $database = 'test',
        public array $options = [],
    ) {}
}

// 使用配置
$config = DatabaseConfig::fromConfig(config('database'));
```

注意：为了性能和出错的可能性考虑，`fromConfig` 默认是不校验传参的数据的

## 扩展功能

### 自定义验证规则

可以通过重写 `getExtraValidationRules` 方法添加自定义验证规则：

```php
use WebmanTech\DTO\BaseRequestDTO;

class CustomDTO extends BaseRequestDTO
{
    #[ValidationRules(required: true)]
    public string $field;
    
    protected static function getExtraValidationRules(): array
    {
        return [
            'field' => 'unique:users,field'
        ];
    }
}
```

### 自定义错误消息

通过重写 `getValidationRuleMessages` 方法自定义错误消息：

```php
use WebmanTech\DTO\BaseRequestDTO;

class CustomDTO extends BaseRequestDTO
{
    protected static function getValidationRuleMessages(): array
    {
        return [
            'field.required' => '自定义错误消息'
        ];
    }
}
```

### 自定义属性名称

通过重写 `getValidationRuleCustomAttributes` 方法自定义属性名称：

```php
use WebmanTech\DTO\BaseRequestDTO;

class CustomDTO extends BaseRequestDTO
{
    protected static function getValidationRuleCustomAttributes(): array
    {
        return [
            'field' => '字段名称'
        ];
    }
}
```

## 最佳实践

1. **合理使用验证规则**：根据业务需求选择合适的验证规则
2. **类型安全**：充分利用 PHP 的类型系统
3. **枚举使用**：对于固定选项的字段使用枚举类型
4. **嵌套对象**：合理设计嵌套对象结构
5. **性能考虑**：对于大量数据处理，注意性能优化
6. **错误处理**：妥善处理验证失败和转换异常
7. **文档注释**：为 DTO 属性添加清晰的文档注释