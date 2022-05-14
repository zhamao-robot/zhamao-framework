# ZM\Utils\Manager\ProcessManager

## removeProcessState

```php
public function removeProcessState(null|int|string $id_or_name, int $type): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id_or_name | null|int|string |  |
| type | int |  |

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
