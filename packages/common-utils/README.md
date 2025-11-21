# webman-tech/common-utils

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
出来的，请勿直接修改

> 通用工具类组件，屏蔽 Webman/Laravel 等框架差异，提供统一的核心功能 API

## 功能介绍

common-utils 是一个通用工具类组件，旨在屏蔽不同框架（如 Webman、Laravel 等）之间的差异，提供统一的核心功能
API。该组件包含了一系列常用的工具类和函数，方便在不同框架环境中使用。

主要功能包括：

- 统一的配置读取接口
- 统一的日志记录接口
- 统一的容器访问接口
- 运行环境检测
- JSON 编码/解码增强
- 本地路径管理
- 环境变量处理
- 字符编码处理
- 语言区域设置
- 视图渲染

## 安装与配置

通过 Composer 安装：

```bash
composer require webman-tech/common-utils
```

该组件无特殊配置要求，直接使用即可。

## 核心类说明

### Config

统一配置读取类，屏蔽不同框架之间的配置读取差异。

主要方法：

- `get()`: 读取配置项，支持点号分隔的嵌套配置
- `requireFromConfigPath()`: 从 config 目录中引入配置文件

### Log

统一日志记录类，提供跨框架的日志记录接口。

主要方法：

- `channel()`: 获取日志通道实例

### Container

统一容器访问类，屏蔽不同框架之间的依赖注入容器差异。

主要方法：

- `get()`: 从容器中获取实例
- `has()`: 检查容器中是否存在某项
- `make()`: 创建实例

### Runtime

运行环境检测类，用于检测当前运行环境。

主要方法：

- `isWebman()`: 检测是否在 Webman 环境下
- `isLaravel()`: 检测是否在 Laravel 环境下
- `isTest()`: 检测是否在测试环境下
- `isCli()`: 检测是否在 CLI 模式下
- `isWorkerman()`: 检测是否在 Workerman 环境下

### Json

JSON 处理增强类，提供更强大的 JSON 编码和解码功能。

主要方法：

- `encode()`: JSON 编码，支持特殊对象和表达式
- `decode()`: JSON 解码

### Local

本地路径管理类，用于管理项目中的各种路径。

主要方法：

- `getBasePath()`: 获取项目基础路径
- `getRuntimePath()`: 获取项目运行时路径
- `getConfigPath()`: 获取配置文件路径
- `getAppPath()`: 获取应用目录路径
- `getVendorPath()`: 获取 vendor 目录路径
- `combinePath()`: 拼接路径
- `getIp()`: 获取本机 IP
- `getPort()`: 获取本地服务端口

### EnvAttr

环境变量处理类，提供便捷的环境变量操作接口。

主要方法：

- `get()`: 获取环境变量
- `set()`: 设置环境变量
- `has()`: 检查环境变量是否存在
- `load()`: 批量加载环境变量

### Encoding

字符编码处理类，用于处理各种字符编码转换。

主要方法：

- `toUTF8()`: 转换为 UTF-8 编码
- `detect()`: 检测字符串编码
- `convert()`: 转换编码

### Lang

语言区域设置类，用于处理多语言环境。

主要方法：

- `getLocale()`: 获取当前语言区域
- `setLocale()`: 设置当前语言区域

### View

视图渲染类，提供简单的视图渲染功能。

主要方法：

- `renderPHP()`: 渲染 PHP 视图文件

## 使用方法

### 配置读取

使用 [Config](./src/Config.php) 类统一读取不同框架的配置：

```php
use WebmanTech\CommonUtils\Config;

// 读取配置项，支持点号分隔的嵌套配置
$value = Config::get('app.name', 'default');

// 支持闭包作为默认值
$value = Config::get('app.name', function() {
    return 'dynamic default value';
});

// 从 config 目录中引入配置文件
$configArray = Config::requireFromConfigPath('database');
```

### 日志记录

使用 [Log](./src/Log.php) 类统一记录日志：

```php
use WebmanTech\CommonUtils\Log;

// 获取默认日志通道
$logger = Log::channel();
$logger->info('这是一条信息日志');

// 获取指定日志通道
$logger = Log::channel('error');
$logger->error('这是一条错误日志');

// 直接记录日志
Log::channel()->warning('警告信息');
```

### 视图渲染

使用 [View](./src/View.php) 类进行视图渲染：

```php
use WebmanTech\CommonUtils\View;

View::renderPHP('view/index.php', ['name' => '张三']);
```

### 语言

使用 [Lang](./src/Lang.php) 类进行语言处理：

```php
use WebmanTech\CommonUtils\Lang;

echo Lang::getLocale(); // 获取当前语言
Lang::setLocale('zh-CN'); // 设置当前语言
```

### 容器访问

使用 [Container](./src/Container.php) 类统一访问不同框架的依赖注入容器：

```php
use WebmanTech\CommonUtils\Container;

// 从容器中获取实例
$db = Container::get('db');

// 检查容器中是否存在某项
if (Container::has('cache')) {
    $cache = Container::get('cache');
}

// 创建实例
$http = Container::make('http', ['port' => 8080]);
```

除了静态方法外，还提供了对应的便捷函数：

```php
use function WebmanTech\CommonUtils\container_get;
use function WebmanTech\CommonUtils\container_has;
use function WebmanTech\CommonUtils\container_make;

$db = container_get('db');

if (container_has('cache')) {
    $cache = container_get('cache');
}

$http = container_make('http', ['port' => 8080]);
```

### 运行环境检测

使用 [Runtime](./src/Runtime.php) 类检测当前运行环境：

```php
use WebmanTech\CommonUtils\Runtime;

// 检测是否在 Webman 环境下
if (Runtime::isWebman()) {
    echo "当前运行在 Webman 框架中";
}

// 检测是否在 Laravel 环境下
if (Runtime::isLaravel()) {
    echo "当前运行在 Laravel 框架中";
}

// 检测是否在 CLI 模式下
if (Runtime::isCli()) {
    echo "当前在命令行模式下";
}

// 检测是否在 Workerman 环境下
if (Runtime::isWorkerman()) {
    echo "当前运行在 Workerman 环境中";
}
```

### JSON 处理

使用 [Json](./src/Json.php) 类进行增强的 JSON 编码和解码：

```php
use WebmanTech\CommonUtils\Json;

// JSON 编码
$data = ['name' => '张三', 'age' => 30];
$jsonString = Json::encode($data);

// JSON 解码
$decodedData = Json::decode($jsonString);

// 处理特殊对象
class User {
    public $name = '张三';
    public $age = 30;
}

$user = new User();
$jsonString = Json::encode($user);

// 处理 JavaScript 表达式
use WebmanTech\CommonUtils\Json\Expression;

$data = [
    'callback' => new Expression('function() { alert("Hello"); }')
];
$jsonString = Json::encode($data);
// 输出: {"callback":function() { alert("Hello"); }}
```

### 本地路径管理

使用 [Local](./src/Local.php) 类管理本地路径：

```php
use WebmanTech\CommonUtils\Local;

// 获取项目基础路径
$basePath = Local::getBasePath();

// 获取项目运行时路径
$runtimePath = Local::getRuntimePath();

// 获取配置文件路径
$configPath = Local::getConfigPath();

// 获取 app 路径
$appPath = Local::getAppPath();

// 获取 vendor 路径
$vendorPath = Local::getVendorPath();

// 拼接路径
$combinedPath = Local::combinePath($basePath, 'storage', 'logs');

// 获取本机 IP
$localIp = Local::getIp();
```

### 环境变量处理

使用 [EnvAttr](./src/EnvAttr.php) 类处理环境变量：

```php
use WebmanTech\CommonUtils\EnvAttr;

// 获取环境变量
$value = EnvAttr::get('APP_ENV', 'production');

// 设置环境变量
EnvAttr::set('CUSTOM_KEY', 'custom_value');

// 检查环境变量是否存在
if (EnvAttr::has('APP_DEBUG')) {
    echo "APP_DEBUG 已设置";
}

// 批量加载环境变量
$envVars = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306
];
EnvAttr::load($envVars);
```

### 字符编码处理

使用 [Encoding](./src/Encoding.php) 类处理字符编码：

```php
use WebmanTech\CommonUtils\Encoding;

// 转换为 UTF-8 编码
$utf8String = Encoding::toUTF8($originalString);

// 检测字符串编码
$encoding = Encoding::detect($string);

// 转换编码
$convertedString = Encoding::convert($string, 'GBK', 'UTF-8');
```

## 最佳实践

1. 在多框架项目中使用统一的工具类接口，避免直接调用框架特定方法
2. 利用 Runtime 类进行环境检测，编写兼容性更好的代码
3. 使用 Json 类替代原生 json_encode/json_decode，获得更好的错误处理和特殊类型支持
4. 通过 Config 类统一管理配置读取，提高代码可维护性
5. 使用 Local 类管理路径，确保在不同环境中的路径正确性
6. 合理使用 EnvAttr 类处理环境变量，便于配置管理
7. 使用 Container 类访问依赖注入容器，提高代码解耦性
8. 通过 Lang 和 View 类处理国际化和视图渲染需求

## 注意事项

1. 该组件旨在提供统一接口，具体实现会根据运行环境自动适配
2. 在不支持的环境中使用某些功能时会抛出 InvalidArgumentException 异常
3. JSON 处理类对特殊值（如 INF、NAN）和资源类型进行了特殊处理
4. 路径管理类支持测试环境的特殊路径配置