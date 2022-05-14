# ZM\Adapters\OneBot11Adapter

## getName

```php
public function getName(): string
```

### 描述

{@inheritDoc}

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## getVersion

```php
public function getVersion(): string
```

### 描述

{@inheritDoc}

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## handleIncomingRequest

```php
public function handleIncomingRequest(Swoole\WebSocket\Frame $frame, ZM\Context\ContextInterface $context): void
```

### 描述

{@inheritDoc}

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| frame | Swoole\WebSocket\Frame |  |
| context | ZM\Context\ContextInterface |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleAPIResponse

```php
public function handleAPIResponse(array $data, ContextInterface $context): void
```

### 描述

处理 API 响应

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 数据 |
| context | ContextInterface | 上下文 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleMessageEvent

```php
public function handleMessageEvent(array $data, ContextInterface $context): void
```

### 描述

处理消息事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |
| context | ContextInterface | 上下文 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleMetaEvent

```php
public function handleMetaEvent(array $data, ContextInterface $context): void
```

### 描述

处理元事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |
| context | ContextInterface | 上下文 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleNoticeEvent

```php
public function handleNoticeEvent(array $data, ContextInterface $context): void
```

### 描述

处理通知事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |
| context | ContextInterface | 上下文 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleRequestEvent

```php
public function handleRequestEvent(array $data, ContextInterface $context): void
```

### 描述

处理请求事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |
| context | ContextInterface | 上下文 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| void |  |


## handleBeforeEvent

```php
public function handleBeforeEvent(array $data, string $time): ZM\Event\EventDispatcher
```

### 描述

处理前置事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |
| time | string | 执行时机 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Event\EventDispatcher |  |


## handleAfterEvent

```php
public function handleAfterEvent(array $data): ZM\Event\EventDispatcher
```

### 描述

处理后置事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | array | 消息数据 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Event\EventDispatcher |  |
