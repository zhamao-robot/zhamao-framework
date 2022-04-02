# ZM\Store\LightCache

## init

```php
public function init(array $config): bool|mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| config | array | 配置 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool|mixed | 返回失败（false）或创建SwooleTable成功结果 |


## get

```php
public function get(string $key): null|mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| key | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|mixed |  |


## getExpire

```php
public function getExpire(string $key): null|mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| key | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|mixed |  |


## getExpireTS

```php
public function getExpireTS(string $key): null|mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| key | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|mixed |  |


## set

```php
public function set(array|int|string $value, string $key, int $expire): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| value | array|int|string |  |
| key | string |  |
| expire | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## update

```php
public function update(mixed $value, string $key): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| value | mixed |  |
| key | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## isset

```php
public function isset(string $key): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| key | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## savePersistence

```php
public function savePersistence(): mixed
```

### 描述

这个只能在唯一一个工作进程中执行

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
