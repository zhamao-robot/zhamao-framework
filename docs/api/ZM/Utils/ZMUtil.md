# ZM\Utils\ZMUtil

## stop

```php
public function stop(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## reload

```php
public function reload(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getReloadableFiles

```php
public function getReloadableFiles(): string[]|string[][]
```

### 描述

在工作进程中返回可以通过reload重新加载的php文件列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string[]|string[][] |  |


## getClassesPsr4

```php
public function getClassesPsr4(mixed $dir, mixed $base_namespace, null|mixed $rule, bool $return_path_value): string[]
```

### 描述

使用Psr-4标准获取目录下的所有类

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| dir | mixed |  |
| base_namespace | mixed |  |
| rule | null|mixed |  |
| return_path_value | bool |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string[] |  |
