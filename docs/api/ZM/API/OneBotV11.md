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
public function sendGroupMsg(mixed $group_id, mixed $message, bool $auto_escape): null|array|bool
```

### 描述

发送群消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| message | mixed |  |
| auto_escape | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## sendMsg

```php
public function sendMsg(mixed $message_type, mixed $target_id, mixed $message, bool $auto_escape): null|array|bool
```

### 描述

发送消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_type | mixed |  |
| target_id | mixed |  |
| message | mixed |  |
| auto_escape | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## deleteMsg

```php
public function deleteMsg(mixed $message_id): null|array|bool
```

### 描述

撤回消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_id | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getMsg

```php
public function getMsg(mixed $message_id): null|array|bool
```

### 描述

获取消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| message_id | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getForwardMsg

```php
public function getForwardMsg(mixed $id): null|array|bool
```

### 描述

获取合并转发消息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| id | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## sendLike

```php
public function sendLike(mixed $user_id, int $times): null|array|bool
```

### 描述

发送好友赞

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | mixed |  |
| times | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupKick

```php
public function setGroupKick(mixed $group_id, mixed $user_id, bool $reject_add_request): null|array|bool
```

### 描述

群组踢人

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| reject_add_request | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupBan

```php
public function setGroupBan(mixed $group_id, mixed $user_id, int $duration): null|array|bool
```

### 描述

群组单人禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| duration | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupAnonymousBan

```php
public function setGroupAnonymousBan(mixed $group_id, mixed $anonymous_or_flag, int $duration): null|array|bool
```

### 描述

群组匿名用户禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| anonymous_or_flag | mixed |  |
| duration | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupWholeBan

```php
public function setGroupWholeBan(mixed $group_id, bool $enable): null|array|bool
```

### 描述

群组全员禁言

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| enable | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupAdmin

```php
public function setGroupAdmin(mixed $group_id, mixed $user_id, bool $enable): null|array|bool
```

### 描述

群组设置管理员

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| enable | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupAnonymous

```php
public function setGroupAnonymous(mixed $group_id, bool $enable): null|array|bool
```

### 描述

群组匿名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| enable | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupCard

```php
public function setGroupCard(mixed $group_id, mixed $user_id, string $card): null|array|bool
```

### 描述

设置群名片（群备注）

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| card | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupName

```php
public function setGroupName(mixed $group_id, mixed $group_name): null|array|bool
```

### 描述

设置群名

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| group_name | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupLeave

```php
public function setGroupLeave(mixed $group_id, bool $is_dismiss): null|array|bool
```

### 描述

退出群组

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| is_dismiss | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupSpecialTitle

```php
public function setGroupSpecialTitle(mixed $group_id, mixed $user_id, string $special_title, int $duration): null|array|bool
```

### 描述

设置群组专属头衔

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| special_title | string |  |
| duration | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setFriendAddRequest

```php
public function setFriendAddRequest(mixed $flag, bool $approve, string $remark): null|array|bool
```

### 描述

处理加好友请求

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| flag | mixed |  |
| approve | bool |  |
| remark | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setGroupAddRequest

```php
public function setGroupAddRequest(mixed $flag, mixed $sub_type, bool $approve, string $reason): null|array|bool
```

### 描述

处理加群请求／邀请

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| flag | mixed |  |
| sub_type | mixed |  |
| approve | bool |  |
| reason | string |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getLoginInfo

```php
public function getLoginInfo(): null|array|bool
```

### 描述

获取登录号信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getStrangerInfo

```php
public function getStrangerInfo(mixed $user_id, bool $no_cache): null|array|bool
```

### 描述

获取陌生人信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| user_id | mixed |  |
| no_cache | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getFriendList

```php
public function getFriendList(): null|array|bool
```

### 描述

获取好友列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getGroupInfo

```php
public function getGroupInfo(mixed $group_id, bool $no_cache): null|array|bool
```

### 描述

获取群信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| no_cache | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getGroupList

```php
public function getGroupList(): null|array|bool
```

### 描述

获取群列表

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getGroupMemberInfo

```php
public function getGroupMemberInfo(mixed $group_id, mixed $user_id, bool $no_cache): null|array|bool
```

### 描述

获取群成员信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| user_id | mixed |  |
| no_cache | bool |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getGroupMemberList

```php
public function getGroupMemberList(mixed $group_id): null|array|bool
```

### 描述

获取群成员列表

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getGroupHonorInfo

```php
public function getGroupHonorInfo(mixed $group_id, mixed $type): null|array|bool
```

### 描述

获取群荣誉信息

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| group_id | mixed |  |
| type | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getCsrfToken

```php
public function getCsrfToken(): null|array|bool
```

### 描述

获取 CSRF Token

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getCredentials

```php
public function getCredentials(string $domain): null|array|bool
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
| null|array|bool |  |


## getRecord

```php
public function getRecord(mixed $file, mixed $out_format): null|array|bool
```

### 描述

获取语音

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |
| out_format | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getImage

```php
public function getImage(mixed $file): null|array|bool
```

### 描述

获取图片

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| file | mixed |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## canSendImage

```php
public function canSendImage(): null|array|bool
```

### 描述

检查是否可以发送图片

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## canSendRecord

```php
public function canSendRecord(): null|array|bool
```

### 描述

检查是否可以发送语音

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getStatus

```php
public function getStatus(): null|array|bool
```

### 描述

获取运行状态

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## getVersionInfo

```php
public function getVersionInfo(): null|array|bool
```

### 描述

获取版本信息

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## setRestart

```php
public function setRestart(int $delay): null|array|bool
```

### 描述

重启 OneBot 实现

### 参数

| 名称 | 类型 | 描述 |
| -------- | ---- | ----------- |
| delay | int |  |

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


## cleanCache

```php
public function cleanCache(): null|array|bool
```

### 描述

清理缓存

### 返回

| 类型 | 描述 |
| ---- | ----------- |
| null|array|bool |  |


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
| mixed |  |
