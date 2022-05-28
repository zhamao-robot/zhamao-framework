# ZM\Container\ContainerServicesProvider

## registerServices

```php
public function registerServices(string $scope): void
```

### 描述

注册服务
```
作用域：
global: worker start
request: request
message: message
connection: open, close, message
```

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| scope | string | 作用域 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## cleanup

```php
public function cleanup(): void
```

### 描述

清理服务

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## registerGlobalServices

```php
public function registerGlobalServices(ZM\Container\ContainerInterface $container): void
```

### 描述

注册全局服务

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## registerRequestServices

```php
public function registerRequestServices(ZM\Container\ContainerInterface $container): void
```

### 描述

注册请求服务（HTTP请求）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## registerMessageServices

```php
public function registerMessageServices(ZM\Container\ContainerInterface $container): void
```

### 描述

注册消息服务（WS消息）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## registerConnectionServices

```php
public function registerConnectionServices(ZM\Container\ContainerInterface $container): void
```

### 描述

注册链接服务

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |
