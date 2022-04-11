# ZM\API\Proxies\Bot\AbstractBotProxy

## __construct

```php
public function __construct(AbstractBotProxy|ZMRobot $bot): mixed
```

### 描述

构造函数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| bot | AbstractBotProxy|ZMRobot | 调用此代理的机器人实例 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## __call

```php
public function __call(string $name, array $arguments): mixed
```

### 描述

在传入的机器人实例上调用方法

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string | 方法名 |
| arguments | array | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## __get

```php
public function __get(string $name): mixed
```

### 描述

获取传入的机器人实例的属性

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string | 属性名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## __set

```php
public function __set(string $name, mixed $value): mixed
```

### 描述

设置传入的机器人实例的属性

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string | 属性名 |
| value | mixed | 属性值 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## __isset

```php
public function __isset(string $name): bool
```

### 描述

判断传入的机器人实例的属性是否存在

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string | 属性名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |
