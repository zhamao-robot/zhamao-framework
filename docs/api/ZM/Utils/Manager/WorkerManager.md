# ZM\Utils\Manager\WorkerManager

## workerAction

```php
public function workerAction(int $src_worker_id, array $data): mixed
```

### 描述

Worker 进程间通信触发的动作类型函数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| src_worker_id | int | 源 Worker 进程 ID |
| data | array | 数据 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## sendActionToWorker

```php
public function sendActionToWorker(int $worker_id, string $action, mixed $data): mixed
```

### 描述

给 Worker 进程发送动作指令（包括自身，自身将直接执行）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| worker_id | int | 进程ID |
| action | string | 动作 |
| data | mixed | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## resumeAllWorkerCoroutines

```php
public function resumeAllWorkerCoroutines(): mixed
```

### 描述

向所有 Worker 进程发送动作指令

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
