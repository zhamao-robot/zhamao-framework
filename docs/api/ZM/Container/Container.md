# ZM\Container\Container

## getParent

```php
public function getParent(): ZM\Container\ContainerInterface
```

### 描述

获取父容器

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Container\ContainerInterface |  |


## has

```php
public function has(string $id): bool
```

### 描述

Returns true if the container can return an entry for the given identifier.
Returns false otherwise.
`has($id)` returning true does not mean that `get($id)` will not throw an exception.
It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | string | identifier of the entry to look for |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## make

```php
public function make(class-string<T> $abstract, array $parameters): Closure|mixed|T
```

### 描述

获取一个绑定的实例

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | class-string<T> | 类或接口名 |
| parameters | array | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure|mixed|T | 实例 |


## flush

```php
public function flush(): void
```

### 描述

清除所有绑定和实例

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |
