# ZM\Container\WorkerContainer

## getInstance

```php
public function getInstance(mixed $args): object
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| args | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| object |  |


## bound

```php
public function bound(string $abstract): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getAlias

```php
public function getAlias(string $abstract): string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## alias

```php
public function alias(string $abstract, string $alias): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| alias | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## bind

```php
public function bind(string $abstract, mixed $concrete, bool $shared): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| concrete | mixed |  |
| shared | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## bindIf

```php
public function bindIf(string $abstract, mixed $concrete, bool $shared): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| concrete | mixed |  |
| shared | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## singleton

```php
public function singleton(string $abstract, mixed $concrete): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| concrete | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## singletonIf

```php
public function singletonIf(string $abstract, mixed $concrete): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| concrete | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## instance

```php
public function instance(string $abstract, mixed $instance): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| instance | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## factory

```php
public function factory(string $abstract): Closure
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure |  |


## flush

```php
public function flush(): void
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## make

```php
public function make(string $abstract, array $parameters): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| parameters | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## build

```php
public function build(mixed $concrete): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| concrete | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## call

```php
public function call(mixed $callback, array $parameters, string $default_method): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | mixed |  |
| parameters | array |  |
| default_method | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## get

```php
public function get(string $id): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## has

```php
public function has(string $id): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## extend

```php
public function extend(string $abstract, Closure $closure): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| closure | Closure |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getLogPrefix

```php
public function getLogPrefix(): string
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## setLogPrefix

```php
public function setLogPrefix(string $prefix): void
```

### 描述

作者很懒，什么也没有说

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
public function getExtenders(string $abstract): array
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## isAlias

```php
public function isAlias(string $name): bool
```

### 描述

作者很懒，什么也没有说

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

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## getClosure

```php
public function getClosure(string $abstract, string $concrete): Closure
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |
| concrete | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Closure |  |


## getLastParameterOverride

```php
public function getLastParameterOverride(): array
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## notInstantiable

```php
public function notInstantiable(string $concrete, string $reason): void
```

### 描述

作者很懒，什么也没有说

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
public function resolveDependencies(array $dependencies): array
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| dependencies | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


## hasParameterOverride

```php
public function hasParameterOverride(ReflectionParameter $parameter): bool
```

### 描述

作者很懒，什么也没有说

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

作者很懒，什么也没有说

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

作者很懒，什么也没有说

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

作者很懒，什么也没有说

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

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## resolveClass

```php
public function resolveClass(ReflectionParameter $parameter): mixed
```

### 描述

作者很懒，什么也没有说

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
public function getConcrete(string $abstract): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## isBuildable

```php
public function isBuildable(mixed $concrete, string $abstract): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| concrete | mixed |  |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## isShared

```php
public function isShared(string $abstract): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| abstract | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## shouldLog

```php
public function shouldLog(): bool
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## log

```php
public function log(string $message): void
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |
