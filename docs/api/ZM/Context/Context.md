# ZM\Context\Context

## getServer

```php
public function getServer(): Server
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| Server |  |


## getData

```php
public function getData(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## reply

```php
public function reply(array|string $msg, bool|callable|Closure $yield): array|bool
```

### 描述

only can used by cq->message event function

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | array|string | 要回复的消息 |
| yield | bool|callable|Closure | 是否协程挂起（true），是否绑定异步事件（Closure） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果 |


## finalReply

```php
public function finalReply(array|string $msg, bool $yield): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | array|string | 要回复的消息 |
| yield | bool | 是否协程挂起（true），是否绑定异步事件（Closure） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## waitMessage

```php
public function waitMessage(string $prompt, int $timeout, string $timeout_prompt): string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt | string |  |
| timeout | int |  |
| timeout_prompt | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 返回用户输入的内容 |


## getArgs

```php
public function getArgs(int|string $mode, string|Stringable $prompt_msg): float|int|string
```

### 描述

根据选定的模式获取消息参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| mode | int|string | 获取的模式 |
| prompt_msg | string|Stringable | 提示语回复 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| float|int|string |  |


## getNextArg

```php
public function getNextArg(string $prompt_msg): int|mixed|string
```

### 描述

获取下一个参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string | 提示语回复 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string | 返回获取的参数 |


## getFullArg

```php
public function getFullArg(string $prompt_msg): int|mixed|string
```

### 描述

获取接下来所有的消息当成一个完整的参数（包含空格）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string | 提示语回复 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string | 返回获取的参数 |


## getNumArg

```php
public function getNumArg(string $prompt_msg): int|mixed|string
```

### 描述

获取下一个数字类型的参数

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string | 提示语回复 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string | 返回获取的参数 |


## cloneFromParent

```php
public function cloneFromParent(): ContextInterface
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ContextInterface | 返回上下文 |
