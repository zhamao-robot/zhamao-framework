# ZM\Utils\MessageUtil

## downloadCQImage

```php
public function downloadCQImage(mixed $msg, null $path): array|false
```

### 描述

下载消息中 CQ 码的所有图片，通过 url

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| path | null |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|false |  |


## containsImage

```php
public function containsImage(mixed $msg): bool
```

### 描述

检查消息中是否含有图片 CQ 码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| bool |  |


## getImageCQFromLocal

```php
public function getImageCQFromLocal(mixed $file, int $type): string
```

### 描述

通过本地地址返回图片的 CQ 码
type == 0 : 返回图片的 base64 CQ 码
type == 1 : 返回图片的 file://路径 CQ 码（路径必须为绝对路径）
type == 2 : 返回图片的 http://xxx CQ 码（默认为 /images/ 路径就是文件对应所在的目录）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |
| type | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string |  |


## splitCommand

```php
public function splitCommand(mixed $msg): array|string[]
```

### 描述

分割字符，将用户消息通过空格或换行分割为数组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|string[] |  |


## matchCommand

```php
public function matchCommand(mixed $msg, mixed $obj): ZM\Entity\MatchResult
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| obj | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZM\Entity\MatchResult |  |


## addShortCommand

```php
public function addShortCommand(mixed $command, string $reply): mixed
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| command | mixed |  |
| reply | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed |  |


## strToArray

```php
public function strToArray(mixed $msg, false $trim_text, bool $ignore_space): array
```

### 描述

字符串转数组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | mixed |  |
| trim_text | false |  |
| ignore_space | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array |  |


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
