# ZM\Utils\SignalListener

## signalMaster

```php
public function signalMaster(Swoole\Server $server): mixed
```

### 描述

监听Master进程的Ctrl+C

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| server | Swoole\Server |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## signalManager

```php
public function signalManager(): mixed
```

### 描述

监听Manager进程的Ctrl+C

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## signalWorker

```php
public function signalWorker(mixed $worker_id, Swoole\Server $server): mixed
```

### 描述

监听Worker/TaskWorker进程的Ctrl+C

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| worker_id | mixed |  |
| server | Swoole\Server |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## processKillerPrompt

```php
public function processKillerPrompt(): mixed
```

### 描述

按5次Ctrl+C后强行杀死框架的处理函数

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
