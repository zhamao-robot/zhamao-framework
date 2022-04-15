# ZM\Utils\ReflectionUtil

## getParameterClassName

```php
public function getParameterClassName(ReflectionParameter $parameter): null|string
```

### 描述

获取参数的类名（如有）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| parameter | ReflectionParameter | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|string | 类名，如果参数不是类，返回 null |


## variableToString

```php
public function variableToString(mixed $var): string
```

### 描述

将传入变量转换为字符串

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| var | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## isNonStaticMethod

```php
public function isNonStaticMethod(callable|string $callback): bool
```

### 描述

判断传入的回调是否为任意类的非静态方法

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | callable|string | 回调 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getCallReflector

```php
public function getCallReflector(callable|string $callback): ReflectionFunctionAbstract
```

### 描述

获取传入的回调的反射实例
如果传入的是类方法，则会返回 {@link ReflectionMethod} 实例
否则将返回 {@link ReflectionFunction} 实例
可传入实现了 __invoke 的类

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | callable|string | 回调 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ReflectionFunctionAbstract |  |
