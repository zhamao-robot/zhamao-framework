# ZM\API\GoCqhttpAPIV11

## getGuildServiceProfile

```php
public function getGuildServiceProfile(): array|bool
```

### 描述

获取频道系统内BOT的资料
响应字段：nickname, tiny_id, avatar_url

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGuildList

```php
public function getGuildList(): array|bool
```

### 描述

获取频道列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGuildMetaByGuest

```php
public function getGuildMetaByGuest(int|string $guild_id): array|bool
```

### 描述

通过访客获取频道元数据

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | int|string | 频道ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGuildChannelList

```php
public function getGuildChannelList(int|string $guild_id, false $no_cache): array|bool
```

### 描述

获取子频道列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | int|string | 频道ID |
| no_cache | false | 禁用缓存（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGuildMembers

```php
public function getGuildMembers(int|string $guild_id): array|bool
```

### 描述

获取频道成员列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | int|string | 频道ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## sendGuildChannelMsg

```php
public function sendGuildChannelMsg(int|string $guild_id, int|string $channel_id, string $message): array|bool
```

### 描述

发送信息到子频道

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | int|string | 频道ID |
| channel_id | int|string | 子频道ID |
| message | string | 信息内容 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |
