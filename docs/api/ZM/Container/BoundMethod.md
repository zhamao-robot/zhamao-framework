# ZM\Container\BoundMethod

## call

```php
public function call(ZM\Container\ContainerInterface $container, mixed $callback, array $parameters, string $default_method): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |
| callback | mixed |  |
| parameters | array |  |
| default_method | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getMethodDependencies

```php
public function getMethodDependencies(ZM\Container\ContainerInterface $container, mixed $callback, array $parameters): array
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| container | ZM\Container\ContainerInterface |  |
| callback | mixed |  |
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

作者很懒，什么也没有说

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
