# ZM\Utils\MessageUtil

## downloadCQImage

```php
public function downloadCQImage(array|string $msg, null|string $path): array|false
```

### 描述

下载消息中 CQ 码的所有图片，通过 url

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | array|string | 消息或消息数组 |
| path | null|string | 保存路径 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false | 返回图片信息或失败返回false |


## containsImage

```php
public function containsImage(array|string $msg): bool
```

### 描述

检查消息中是否含有图片 CQ 码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | array|string | 消息或消息数组 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getImageCQFromLocal

```php
public function getImageCQFromLocal(string $file, int $type): string
```

### 描述

通过本地地址返回图片的 CQ 码
type == 0 : 返回图片的 base64 CQ 码
type == 1 : 返回图片的 file://路径 CQ 码（路径必须为绝对路径）
type == 2 : 返回图片的 http://xxx CQ 码（默认为 /images/ 路径就是文件对应所在的目录）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件数据 |
| type | int | 文件类型（0，1，2可选，默认为0） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## splitCommand

```php
public function splitCommand(string $msg): array|string[]
```

### 描述

分割字符，将用户消息通过空格或换行分割为数组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息内容 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|string[] |  |


## matchCommand

```php
public function matchCommand(array|string $msg, array|Iterator $obj): ZM\Entity\MatchResult
```

### 描述

根据CQCommand的规则匹配消息，获取是否匹配到对应的注解事件

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | array|string | 消息内容 |
| obj | array|Iterator | 数据对象 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Entity\MatchResult |  |


## addShortCommand

```php
public function addShortCommand(string $command, string $reply): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| command | string | 命令内容 |
| reply | string | 回复内容 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## strToArray

```php
public function strToArray(string $msg, bool $ignore_space, bool $trim_text): array
```

### 描述

字符串转数组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息内容 |
| ignore_space | bool | 是否忽略空行 |
| trim_text | bool | 是否去除空格 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array | 返回数组 |


## arrayToStr

```php
public function arrayToStr(array $array): string
```

### 描述

数组转字符串
纪念一下，这段代码完全由AI生成，没有人知道它是怎么写的，这句话是我自己写的，不知道是不是有人知道的

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| array | array |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## generateCommandHelp

```php
public function generateCommandHelp(): array
```

### 描述

根据注解树生成命令列表、帮助

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array | 帮助信息，每个元素对应一个命令的帮助信息，格式为：命令名（其他触发条件）：命令描述 |
