# TaskManager 工作进程管理

此类管理的是 TaskWorker 相关工作。有关使用 TaskWorker 的教程，见 [进阶 - 使用 TaskWorker 进程处理密集运算](/advanced/task-worker)

类定义：`\ZM\Utils\TaskManager`

使用 TaskWorker 需要先在 `global.php` 配置文件中开启！

## 方法

### runTask()

在 TaskWorker 运行任务。

定义：`runTask($task_name, $timeout = -1, ...$params)`

参数 `$task_name`：对应 `@OnTask` 注解绑定的任务函数。

参数 `$timeout`：等待任务函数最长运行的时间（秒），如果超过此时间将返回 false。

参数 `剩余`：将变量传入 TaskWorker 进程，除 Closure，资源类型外，可序列化的变量均可。

```php
TaskManager::runTask("heavy_task", 100, "param1", "param2");
```

