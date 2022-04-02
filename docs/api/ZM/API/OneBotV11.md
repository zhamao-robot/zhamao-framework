# ZM\API\OneBotV11

## get

```php
public function get(int|string $robot_id): ZMRobot
```

### 描述

获取机器人Action/API实例

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| robot_id | int|string | 机器人ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZMRobot | 机器人实例 |


## getRandom

```php
public function getRandom(): ZMRobot
```

### 描述

随机获取一个连接到框架的机器人实例

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZMRobot | 机器人实例 |


## getAllRobot

```php
public function getAllRobot(): ZMRobot[]
```

### 描述

获取所有机器人实例

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| ZMRobot[] | 机器人实例们 |


## setCallback

```php
public function setCallback(bool|Closure $callback): OneBotV11
```

### 描述

设置回调或启用协程等待API回包

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| callback | bool|Closure | 是否开启协程或设置异步回调函数，如果为true，则协程等待结果，如果为false，则异步执行并不等待结果，如果为回调函数，则异步执行且调用回调 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| OneBotV11 | 返回本身 |


## setPrefix

```php
public function setPrefix(int $prefix): OneBotV11
```

### 描述

设置API调用类型后缀

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| prefix | int | 设置后缀类型，API_NORMAL为不加后缀，API_ASYNC为异步调用，API_RATE_LIMITED为加后缀并且限制调用频率 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| OneBotV11 | 返回本身 |


## sendPrivateMsg

```php
public function sendPrivateMsg(int|string $user_id, string $message, bool $auto_escape): array|bool
```

### 描述

发送私聊消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | int|string | 用户ID |
| message | string | 消息内容 |
| auto_escape | bool | 是否自动转义（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## sendGroupMsg

```php
public function sendGroupMsg(int|string $group_id, string $message, bool $auto_escape): array|bool
```

### 描述

发送群消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群组ID |
| message | string | 消息内容 |
| auto_escape | bool | 是否自动转义（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## sendMsg

```php
public function sendMsg(string $message_type, int|string $target_id, string $message, bool $auto_escape): array|bool
```

### 描述

发送消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_type | string | 消息类型 |
| target_id | int|string | 目标ID |
| message | string | 消息内容 |
| auto_escape | bool | 是否自动转义（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## deleteMsg

```php
public function deleteMsg(int|string $message_id): array|bool
```

### 描述

撤回消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_id | int|string | 消息ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getMsg

```php
public function getMsg(int|string $message_id): array|bool
```

### 描述

获取消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_id | int|string | 消息ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getForwardMsg

```php
public function getForwardMsg(int|string $id): array|bool
```

### 描述

获取合并转发消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | int|string | ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## sendLike

```php
public function sendLike(int|string $user_id, int $times): array|bool
```

### 描述

发送好友赞

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | int|string | 用户ID |
| times | int | 时间 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupKick

```php
public function setGroupKick(int|string $group_id, int|string $user_id, bool $reject_add_request): array|bool
```

### 描述

群组踢人

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| reject_add_request | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupBan

```php
public function setGroupBan(int|string $group_id, int|string $user_id, int $duration): array|bool
```

### 描述

群组单人禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| duration | int | 禁言时长 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupAnonymousBan

```php
public function setGroupAnonymousBan(int|string $group_id, array|int|string $anonymous_or_flag, int $duration): array|bool
```

### 描述

群组匿名用户禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| anonymous_or_flag | array|int|string | 匿名禁言Flag或匿名用户对象 |
| duration | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupWholeBan

```php
public function setGroupWholeBan(int|string $group_id, bool $enable): array|bool
```

### 描述

群组全员禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| enable | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupAdmin

```php
public function setGroupAdmin(int|string $group_id, int|string $user_id, bool $enable): array|bool
```

### 描述

群组设置管理员

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| enable | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupAnonymous

```php
public function setGroupAnonymous(int|string $group_id, bool $enable): array|bool
```

### 描述

群组匿名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| enable | bool | 是否启用（默认为true） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupCard

```php
public function setGroupCard(int|string $group_id, int|string $user_id, string $card): array|bool
```

### 描述

设置群名片（群备注）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| card | string | 名片内容（默认为空） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupName

```php
public function setGroupName(int|string $group_id, string $group_name): array|bool
```

### 描述

设置群名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| group_name | string | 群名 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupLeave

```php
public function setGroupLeave(int|string $group_id, bool $is_dismiss): array|bool
```

### 描述

退出群组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| is_dismiss | bool | 是否解散（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupSpecialTitle

```php
public function setGroupSpecialTitle(int|string $group_id, int|string $user_id, string $special_title, int $duration): array|bool
```

### 描述

设置群组专属头衔

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| special_title | string | 专属头衔内容 |
| duration | int | 持续时间（默认为-1，永久） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setFriendAddRequest

```php
public function setFriendAddRequest(array|int|string $flag, bool $approve, string $remark): array|bool
```

### 描述

处理加好友请求

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| flag | array|int|string | 处理加好友请求的flag |
| approve | bool | 是否同意（默认为true） |
| remark | string | 设置昵称（默认不设置） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setGroupAddRequest

```php
public function setGroupAddRequest(array|int|string $flag, string $sub_type, bool $approve, string $reason): array|bool
```

### 描述

处理加群请求／邀请

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| flag | array|int|string | 处理加群请求的flag |
| sub_type | string | 处理请求类型（包含add和invite） |
| approve | bool | 是否同意（默认为true） |
| reason | string | 拒绝理由（仅在拒绝时有效，默认为空） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getLoginInfo

```php
public function getLoginInfo(): array|bool
```

### 描述

获取登录号信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getStrangerInfo

```php
public function getStrangerInfo(int|string $user_id, bool $no_cache): array|bool
```

### 描述

获取陌生人信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | int|string | 用户ID |
| no_cache | bool | 是否不使用缓存（默认为false） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getFriendList

```php
public function getFriendList(): array|bool
```

### 描述

获取好友列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGroupInfo

```php
public function getGroupInfo(int|string $group_id, bool $no_cache): array|bool
```

### 描述

获取群信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| no_cache | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGroupList

```php
public function getGroupList(): array|bool
```

### 描述

获取群列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGroupMemberInfo

```php
public function getGroupMemberInfo(int|string $group_id, int|string $user_id, bool $no_cache): array|bool
```

### 描述

获取群成员信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| user_id | int|string | 用户ID |
| no_cache | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGroupMemberList

```php
public function getGroupMemberList(int|string $group_id): array|bool
```

### 描述

获取群成员列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getGroupHonorInfo

```php
public function getGroupHonorInfo(int|string $group_id, string $type): array|bool
```

### 描述

获取群荣誉信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | int|string | 群ID |
| type | string | 荣誉类型 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getCsrfToken

```php
public function getCsrfToken(): array|bool
```

### 描述

获取 CSRF Token

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getCredentials

```php
public function getCredentials(string $domain): array|bool
```

### 描述

获取 QQ 相关接口凭证

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| domain | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getRecord

```php
public function getRecord(string $file, string $out_format): array|bool
```

### 描述

获取语音

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件 |
| out_format | string | 输出格式 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getImage

```php
public function getImage(string $file): array|bool
```

### 描述

获取图片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | string | 文件 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## canSendImage

```php
public function canSendImage(): array|bool
```

### 描述

检查是否可以发送图片

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## canSendRecord

```php
public function canSendRecord(): array|bool
```

### 描述

检查是否可以发送语音

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getStatus

```php
public function getStatus(): array|bool
```

### 描述

获取运行状态

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getVersionInfo

```php
public function getVersionInfo(): array|bool
```

### 描述

获取版本信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## setRestart

```php
public function setRestart(int $delay): array|bool
```

### 描述

重启 OneBot 实现

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| delay | int | 延迟时间（毫秒，默认为0） |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## cleanCache

```php
public function cleanCache(): array|bool
```

### 描述

清理缓存

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |


## getExtendedAPI

```php
public function getExtendedAPI(string $package_name): mixed
```

### 描述

获取内置支持的扩展API对象
现支持 go-cqhttp 的扩展API

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| package_name | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| mixed | 返回包的操作对象 |


## callExtendedAPI

```php
public function callExtendedAPI(string $action, array $params): array|bool
```

### 描述

作者很懒，什么也没有说

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| action | string | 动作（API）名称 |
| params | array | 参数 |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| array|bool | 返回API调用结果（数组）或异步API调用状态（bool） |
