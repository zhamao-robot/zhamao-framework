# ZM\Framework

## saveProcessState

```php
public function saveProcessState(int|string $pid, int $type, array $data): mixed
```

### 描述

将各进程的pid写入文件，以备后续崩溃及僵尸进程处理使用

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| pid | int|string |  |
| type | int |  |
| data | array |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getProcessState

```php
public function getProcessState(mixed $id_or_name, int $type): false|int|mixed
```

### 描述

用于框架内部获取多进程运行状态的函数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id_or_name | mixed |  |
| type | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| false|int|mixed |  |


## removeProcessState

```php
public function removeProcessState(null $id_or_name, int $type): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id_or_name | null |  |
| type | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## loadServerEvents

```php
public function loadServerEvents(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## registerServerEvents

```php
public function registerServerEvents(): mixed
```

### 描述

从全局配置文件里读取注入系统事件的类

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## parseCliArgs

```php
public function parseCliArgs(mixed $args, mixed $add_port): mixed
```

### 描述

解析命令行的 $argv 参数们

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| args | mixed |  |
| add_port | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
