# ZM\Container\BoundMethod

## call

```php
public function call(Container $container, callable|string $callback, array $parameters, string $default_method): mixed
```

### 描述

调用指定闭包、类方法并注入依赖

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | Container |  |
| callback | callable|string |  |
| parameters | array |  |
| default_method | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getMethodDependencies

```php
public function getMethodDependencies(callable|string $callback, ZM\Container\ContainerInterface $container, array $parameters): array
```

### 描述

Get all dependencies for a given method.

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | callable|string |  |
| container | ZM\Container\ContainerInterface |  |
| parameters | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## addDependencyForCallParameter

```php
public function addDependencyForCallParameter(ZM\Container\ContainerInterface $container, ReflectionParameter $parameter, array $parameters, array $dependencies): void
```

### 描述

Get the dependency for the given call parameter.

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |
| parameter | ReflectionParameter |  |
| parameters | array |  |
| dependencies | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |
