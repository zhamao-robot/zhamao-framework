# ZM\API\CQ

## at

```php
public function at(mixed $qq): string
```

### 描述

at一下QQ用户（仅在QQ群支持at全体）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| qq | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## face

```php
public function face(mixed $id): string
```

### 描述

发送QQ原生表情

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## image

```php
public function image(mixed $file, bool $cache, bool $flash, bool $proxy, int $timeout): string
```

### 描述

发送图片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |
| cache | bool |  |
| flash | bool |  |
| proxy | bool |  |
| timeout | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## record

```php
public function record(mixed $file, bool $magic, bool $cache, bool $proxy, int $timeout): string
```

### 描述

发送语音

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |
| magic | bool |  |
| cache | bool |  |
| proxy | bool |  |
| timeout | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## video

```php
public function video(mixed $file, bool $cache, bool $proxy, int $timeout): string
```

### 描述

发送短视频

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |
| cache | bool |  |
| proxy | bool |  |
| timeout | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## rps

```php
public function rps(): string
```

### 描述

发送投掷骰子（只能在单条回复中单独使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## dice

```php
public function dice(): string
```

### 描述

发送掷骰子表情（只能在单条回复中单独使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## shake

```php
public function shake(): string
```

### 描述

戳一戳（原窗口抖动，仅支持好友消息使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## poke

```php
public function poke(mixed $type, mixed $id, string $name): string
```

### 描述

发送新的戳一戳

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | mixed |  |
| id | mixed |  |
| name | string |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## anonymous

```php
public function anonymous(int $ignore): string
```

### 描述

发送匿名消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| ignore | int |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## share

```php
public function share(mixed $url, mixed $title, null $content, null $image): string
```

### 描述

发送链接分享（只能在单条回复中单独使用）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| url | mixed |  |
| title | mixed |  |
| content | null |  |
| image | null |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## contact

```php
public function contact(mixed $type, mixed $id): string
```

### 描述

发送好友或群推荐名片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | mixed |  |
| id | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## location

```php
public function location(mixed $lat, mixed $lon, string $title, string $content): string
```

### 描述

发送位置

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| lat | mixed |  |
| lon | mixed |  |
| title | string |  |
| content | string |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## music

```php
public function music(mixed $type, mixed $id_or_url, null $audio, null $title, null $content, null $image): string
```

### 描述

发送音乐分享（只能在单条回复中单独使用）
qq、163、xiami为内置分享，需要先通过搜索功能获取id后使用
custom为自定义分享
当为自定义分享时：
$id_or_url 为音乐卡片点进去打开的链接（一般是音乐介绍界面啦什么的）
$audio 为音乐（如mp3文件）的HTTP链接地址（不可为空）
$title 为音乐卡片的标题，建议12字以内（不可为空）
$content 为音乐卡片的简介（可忽略）
$image 为音乐卡片的图片链接地址（可忽略）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | mixed |  |
| id_or_url | mixed |  |
| audio | null |  |
| title | null |  |
| content | null |  |
| image | null |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## decode

```php
public function decode(mixed $msg, mixed $is_content): mixed
```

### 描述

反转义字符串中的CQ码敏感符号

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| is_content | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## escape

```php
public function escape(mixed $msg, mixed $is_content): mixed
```

### 描述

转义CQ码的特殊字符，同encode

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| is_content | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## encode

```php
public function encode(mixed $msg, mixed $is_content): mixed
```

### 描述

转义CQ码的特殊字符

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| is_content | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## removeCQ

```php
public function removeCQ(mixed $msg): string
```

### 描述

移除消息中所有的CQ码并返回移除CQ码后的消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## getCQ

```php
public function getCQ(mixed $msg, mixed $is_object): mixed
```

### 描述

获取消息中第一个CQ码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| is_object | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## getAllCQ

```php
public function getAllCQ(mixed $msg, mixed $is_object): mixed
```

### 描述

获取消息中所有的CQ码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| is_object | mixed |  |
### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |
