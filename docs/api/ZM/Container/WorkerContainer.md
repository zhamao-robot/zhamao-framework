# ZM\Container\WorkerContainer

## bound

```php
public function bound(string $abstract): bool
```

### 描述

判断对应的类或接口是否已经注册

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getAlias

```php
public function getAlias(string $abstract): string
```

### 描述

获取类别名（如存在）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 别名，不存在时返回传入的类或接口名 |


## alias

```php
public function alias(string $abstract, string $alias): void
```

### 描述

注册一个类别名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| alias | string | 别名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## bind

```php
public function bind(string $abstract, null|Closure|string $concrete, bool $shared): void
```

### 描述

注册绑定

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| concrete | null|Closure|string | 返回类实例的闭包，或是类名 |
| shared | bool | 是否共享 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## bindIf

```php
public function bindIf(string $abstract, null|Closure|string $concrete, bool $shared): void
```

### 描述

注册绑定
在已经绑定时不会重复注册

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| concrete | null|Closure|string | 返回类实例的闭包，或是类名 |
| shared | bool | 是否共享 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## singleton

```php
public function singleton(string $abstract, null|Closure|string $concrete): void
```

### 描述

注册一个单例绑定

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| concrete | null|Closure|string | 返回类实例的闭包，或是类名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## singletonIf

```php
public function singletonIf(string $abstract, null|Closure|string $concrete): void
```

### 描述

注册一个单例绑定
在已经绑定时不会重复注册

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| concrete | null|Closure|string | 返回类实例的闭包，或是类名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## instance

```php
public function instance(string $abstract, mixed $instance): mixed
```

### 描述

注册一个已有的实例，效果等同于单例绑定

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| instance | mixed | 实例 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## factory

```php
public function factory(string $abstract): Closure
```

### 描述

获取一个解析对应类实例的闭包

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure |  |


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


## build

```php
public function build(Closure|string $concrete): mixed
```

### 描述

实例化具体的类实例

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| concrete | Closure|string | 类名或对应的闭包 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## call

```php
public function call(callable|string $callback, array $parameters, null|string $default_method): mixed
```

### 描述

调用对应的方法，并自动注入依赖

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | callable|string | 对应的方法 |
| parameters | array | 参数 |
| default_method | null|string | 默认方法 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## get

```php
public function get(string $id): mixed
```

### 描述

Finds an entry of the container by its identifier and returns it.

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | string | identifier of the entry to look for * |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed | entry |


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


## extend

```php
public function extend(string $abstract, Closure $closure): void
```

### 描述

扩展一个类或接口

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| closure | Closure | 扩展闭包 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getLogPrefix

```php
public function getLogPrefix(): string
```

### 描述

获取日志前缀

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## setLogPrefix

```php
public function setLogPrefix(string $prefix): void
```

### 描述

设置日志前缀

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prefix | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getExtenders

```php
public function getExtenders(string $abstract): Closure[]
```

### 描述

获取对应类型的所有扩展器

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure[] |  |


## isAlias

```php
public function isAlias(string $name): bool
```

### 描述

判断传入的是否为别名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| name | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## dropStaleInstances

```php
public function dropStaleInstances(string $abstract): void
```

### 描述

抛弃所有过时的实例和别名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getClosure

```php
public function getClosure(string $abstract, string $concrete): Closure
```

### 描述

获取一个解析对应类的闭包

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |
| concrete | string | 实际类名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure |  |


## getLastParameterOverride

```php
public function getLastParameterOverride(): array
```

### 描述

获取最后一次的覆盖参数

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## notInstantiable

```php
public function notInstantiable(string $concrete, string $reason): void
```

### 描述

抛出实例化异常

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| concrete | string |  |
| reason | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## resolveDependencies

```php
public function resolveDependencies(ReflectionParameter[] $dependencies): array
```

### 描述

解析依赖

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| dependencies | ReflectionParameter[] |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## hasParameterOverride

```php
public function hasParameterOverride(ReflectionParameter $parameter): bool
```

### 描述

判断传入的参数是否存在覆盖参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getParameterOverride

```php
public function getParameterOverride(ReflectionParameter $parameter): mixed
```

### 描述

获取覆盖参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## hasParameterTypeOverride

```php
public function hasParameterTypeOverride(ReflectionParameter $parameter): bool
```

### 描述

判断传入的参数是否存在临时注入的参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getParameterTypeOverride

```php
public function getParameterTypeOverride(ReflectionParameter $parameter): mixed
```

### 描述

获取临时注入的参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## resolvePrimitive

```php
public function resolvePrimitive(ReflectionParameter $parameter): mixed
```

### 描述

解析基本类型

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed | 对应类型的默认值 |


## resolveClass

```php
public function resolveClass(ReflectionParameter $parameter): mixed
```

### 描述

解析类

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getConcrete

```php
public function getConcrete(string $abstract): Closure|string
```

### 描述

获取类名的实际类型

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure|string |  |


## isBuildable

```php
public function isBuildable(mixed $concrete, string $abstract): bool
```

### 描述

判断传入的实际类型是否可以构造

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| concrete | mixed | 实际类型 |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## isShared

```php
public function isShared(string $abstract): bool
```

### 描述

判断传入的类型是否为共享实例

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string | 类或接口名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## shouldLog

```php
public function shouldLog(): bool
```

### 描述

判断是否输出日志

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## log

```php
public function log(string $message): void
```

### 描述

记录日志（自动附加容器日志前缀）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getInstance

```php
public function getInstance(): static
```

### 描述

获取类实例

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| static |  |
