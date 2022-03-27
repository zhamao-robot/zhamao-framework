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
| array|bool |  |


## getGuildList

```php
public function getGuildList(): array|bool
```

### 描述

获取频道列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## getGuildMetaByGuest

```php
public function getGuildMetaByGuest(mixed $guild_id): array|bool
```

### 描述

通过访客获取频道元数据

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## getGuildChannelList

```php
public function getGuildChannelList(mixed $guild_id, false $no_cache): array|bool
```

### 描述

获取子频道列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | mixed |  |
| no_cache | false |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## getGuildMembers

```php
public function getGuildMembers(mixed $guild_id): array|bool
```

### 描述

获取频道成员列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## sendGuildChannelMsg

```php
public function sendGuildChannelMsg(mixed $guild_id, mixed $channel_id, mixed $message): array|bool
```

### 描述

发送信息到子频道

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| guild_id | mixed |  |
| channel_id | mixed |  |
| message | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## processAPI

```php
public function processAPI(mixed $connection, mixed $reply, |null $function): array|bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| connection | mixed |  |
| reply | mixed |  |
| function | |null |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool |  |


## processHttpAPI

```php
public function processHttpAPI(mixed $connection, mixed $reply, null $function): bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| connection | mixed |  |
| reply | mixed |  |
| function | null |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |
