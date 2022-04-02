# ZM\API\CQ

## at

```php
public function at(int|string $qq): string
```

### 描述

at一下QQ用户（仅在QQ群支持at全体）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| qq | int|string | 用户QQ号/ID号 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## face

```php
public function face(int|string $id): string
```

### 描述

发送QQ原生表情

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | int|string | 表情ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## image

```php
public function image(string $file, bool $cache, bool $flash, bool $proxy, int $timeout): string
```

### 描述

发送图片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件的路径、URL或者base64编码的图片数据 |
| cache | bool | 是否缓存（默认为true） |
| flash | bool | 是否闪照（默认为false） |
| proxy | bool | 是否使用代理（默认为true） |
| timeout | int | 超时时间（默认不超时） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## record

```php
public function record(string $file, bool $magic, bool $cache, bool $proxy, int $timeout): string
```

### 描述

发送语音

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件的路径、URL或者base64编码的语音数据 |
| magic | bool | 是否加特技（默认为false） |
| cache | bool | 是否缓存（默认为true） |
| proxy | bool | 是否使用代理（默认为true） |
| timeout | int | 超时时间（默认不超时） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## video

```php
public function video(string $file, bool $cache, bool $proxy, int $timeout): string
```

### 描述

发送短视频

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件的路径、URL或者base64编码的短视频数据 |
| cache | bool | 是否缓存（默认为true） |
| proxy | bool | 是否使用代理（默认为true） |
| timeout | int | 超时时间（默认不超时） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## rps

```php
public function rps(): string
```

### 描述

发送投掷骰子（只能在单条回复中单独使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## dice

```php
public function dice(): string
```

### 描述

发送掷骰子表情（只能在单条回复中单独使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## shake

```php
public function shake(): string
```

### 描述

戳一戳（原窗口抖动，仅支持好友消息使用）

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## poke

```php
public function poke(int|string $type, int|string $id, string $name): string
```

### 描述

发送新的戳一戳

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | int|string | 焯一戳类型 |
| id | int|string | 戳一戳ID号 |
| name | string | 戳一戳名称（可选） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## anonymous

```php
public function anonymous(int $ignore): string
```

### 描述

发送匿名消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| ignore | int | 是否忽略错误（默认为1，0表示不忽略错误） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## share

```php
public function share(string $url, string $title, null|string $content, null|string $image): string
```

### 描述

发送链接分享（只能在单条回复中单独使用）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| url | string | 分享地址 |
| title | string | 标题 |
| content | null|string | 卡片内容（可选） |
| image | null|string | 卡片图片（可选） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## contact

```php
public function contact(string $type, int|string $id): string
```

### 描述

发送好友或群推荐名片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | string | 名片类型 |
| id | int|string | 好友或群ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## location

```php
public function location(float|string $lat, float|string $lon, string $title, string $content): string
```

### 描述

发送位置

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| lat | float|string | 纬度 |
| lon | float|string | 经度 |
| title | string | 标题（可选） |
| content | string | 卡片内容（可选） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## music

```php
public function music(string $type, int|string $id_or_url, null|string $audio, null|string $title, null|string $content, null|string $image): string
```

### 描述

发送音乐分享（只能在单条回复中单独使用）
qq、163、xiami为内置分享，需要先通过搜索功能获取id后使用

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type | string | 分享类型（仅限 `qq`、`163`、`xiami` 或 `custom`） |
| id_or_url | int|string | 当分享类型不是 `custom` 时，表示的是分享音乐的ID（需要先通过搜索功能获取id后使用），反之表示的是音乐卡片点入的链接 |
| audio | null|string | 当分享类型是 `custom` 时，表示为音乐（如mp3文件）的HTTP链接地址（不可为空） |
| title | null|string | 当分享类型是 `custom` 时，表示为音乐卡片的标题，建议12字以内（不可为空） |
| content | null|string | 当分享类型是 `custom` 时，表示为音乐卡片的简介（可忽略） |
| image | null|string | 当分享类型是 `custom` 时，表示为音乐卡片的图片链接地址（可忽略） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## forward

```php
public function forward(int|string $id): string
```

### 描述

合并转发消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | int|string | 合并转发ID, 需要通过 `/get_forward_msg` API获取转发的具体内容 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## node

```php
public function node(int|string $user_id, string $nickname, string $content): string
```

### 描述

合并转发消息节点
特殊说明: 需要使用单独的API /send_group_forward_msg 发送, 并且由于消息段较为复杂, 仅支持Array形式入参。
如果引用消息和自定义消息同时出现, 实际查看顺序将取消息段顺序。
另外按 CQHTTP 文档说明, data 应全为字符串, 但由于需要接收message 类型的消息, 所以 仅限此Type的content字段 支持Array套娃

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | int|string | 转发消息id |
| nickname | string | 发送者显示名字 |
| content | string | 具体消息 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## xml

```php
public function xml(string $data): string
```

### 描述

XML消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | string | xml内容, xml中的value部分 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## json

```php
public function json(string $data, int $resid): string
```

### 描述

JSON消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| data | string | json内容 |
| resid | int | 0为走小程序通道，其他值为富文本通道（默认为0） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## _custom

```php
public function _custom(string $type_name, array $params): string
```

### 描述

返回一个自定义扩展的CQ码（支持自定义类型和参数）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| type_name | string | CQ码类型名称 |
| params | array | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | CQ码 |


## decode

```php
public function decode(string $msg, bool $is_content): string
```

### 描述

反转义字符串中的CQ码敏感符号

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 字符串 |
| is_content | bool | 如果是解码CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 转义后的CQ码 |


## replace

```php
public function replace(string $str): string
```

### 描述

简单反转义替换CQ码的方括号

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| str | string | 字符串 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 字符串 |


## escape

```php
public function escape(string $msg, bool $is_content): string
```

### 描述

转义CQ码的特殊字符，同encode

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 字符串 |
| is_content | bool | 如果是转义CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 转义后的CQ码 |


## encode

```php
public function encode(string $msg, bool $is_content): string
```

### 描述

转义CQ码的特殊字符

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 字符串 |
| is_content | bool | 如果是转义CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 转义后的CQ码 |


## removeCQ

```php
public function removeCQ(string $msg): string
```

### 描述

移除消息中所有的CQ码并返回移除CQ码后的消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| string | 消息内容 |


## getCQ

```php
public function getCQ(string $msg, bool $is_object): null|array|CQObject
```

### 描述

获取消息中第一个CQ码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息内容 |
| is_object | bool | 是否以对象形式返回，如果为False的话，返回数组形式（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|CQObject | 返回的CQ码（数组或对象） |


## getAllCQ

```php
public function getAllCQ(string $msg, bool $is_object): array|CQObject[]
```

### 描述

获取消息中所有的CQ码

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| msg | string | 消息内容 |
| is_object | bool | 是否以对象形式返回，如果为False的话，返回数组形式（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|CQObject[] | 返回的CQ码们（数组或对象） |
