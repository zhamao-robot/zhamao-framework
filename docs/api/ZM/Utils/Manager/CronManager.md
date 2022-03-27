# ZM\Utils\Manager\CronManager

## initCronTasks

```php
public function initCronTasks(): mixed
```

### 描述

初始化 Cron 注解
必须在 WorkerStart 事件中调用

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## startExecute

```php
public function startExecute(ZM\Annotation\Cron\Cron $v, ZM\Event\EventDispatcher $dispatcher, Cron\CronExpression $cron): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| v | ZM\Annotation\Cron\Cron |  |
| dispatcher | ZM\Event\EventDispatcher |  |
| cron | Cron\CronExpression |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
