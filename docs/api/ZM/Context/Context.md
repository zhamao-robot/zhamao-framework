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
public function reply(mixed $msg, bool $yield): array|bool
```

### 描述

only can used by cq->message event function

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| yield | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## finalReply

```php
public function finalReply(mixed $msg, bool $yield): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| yield | bool |  |

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
| string |  |


## getArgs

```php
public function getArgs(mixed $mode, mixed $prompt_msg): mixed|string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| mode | mixed |  |
| prompt_msg | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed|string |  |


## getNextArg

```php
public function getNextArg(string $prompt_msg): int|mixed|string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string |  |


## getFullArg

```php
public function getFullArg(string $prompt_msg): int|mixed|string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string |  |


## getNumArg

```php
public function getNumArg(string $prompt_msg): int|mixed|string
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prompt_msg | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| int|mixed|string |  |


## cloneFromParent

```php
public function cloneFromParent(): mixed
```

### 描述

作者很懒，什么也没有说

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
