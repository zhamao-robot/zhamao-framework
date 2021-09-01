# 机器人 API（OneBotV11）

OneBotV11 类是封装好的 OneBot 标准的 API 接口调用类，可以在机器人连接后通过连接或者机器人 QQ 号获取对象并调用接口（如发送群消息、获取群列表等操作）。

| 属性项   | 属性值             | 备注                             |
| -------- | ------------------ | -------------------------------- |
| 名称     | OneBotV11          |                                  |
| 类型     | 实例化类           | `$r = new OneBotV11($conn)`      |
| 命名空间 | `ZM\API\OneBotV11` | 使用前先 `use ZM\API\OneBotV11;` |
| 别名     | `ZM\API\ZMRobot`   | 此类目前是 `extends OneBotV11`   |

> 你也可以继续使用 2.5 版本之前的别名类 `ZMRobot`，但未来框架将会优先兼容 OneBot V12 版本的协议，可能会造成更新问题，建议切换为 OneBotV11 类。

## 属性

对象属性方法是对 API 的调整，例如是否以 `_async`、`_rate_limited` 后缀发送 API、设置协程返回还是异步返回结果等。

### OneBotV11::API_NORMAL

以默认（无后缀）方式请求 API。

### OneBotV11::API_ASYNC

以后缀 `_async` 方式异步请求 API。

### OneBotV11::API_RATE_LIMITED

以后缀 `_rate_limited` 方式请求 API。

## 方法

### setPrefix()

设置后缀。目前支持 `_async`、`_rate_limited`。

- **prefix**: `int` `默认:API_NORMAL`，可选 `OneBotV11::API_NORMAL`、`OneBotV11::API_ASYNC`、`OneBotV11::API_RATE_LIMITED`

设置后缀后，请求的 API 会发生变化。例如发送私聊消息：`sendPrivateMsg()`，请求的 API 为 `send_private_msg_async`，详见 [OneBot 文档](https://github.com/howmanybots/onebot/blob/master/v11/specs/api/README.md)。

### setCallback()

设置 API 结果返回方式。默认为 true，就是直接通过框架处理后接收回包直接返回给结果。如果为 false，则 API 请求后只返回是否成功推送出 WS 数据包。

### getSelfId()

获取当前对象的机器人 QQ 或 OneBot 实例的 ID。

```php
$bot = OneBotV11::get(123456);
echo $bot->getSelfId(); //123456
```

### OneBotV11::get()

静态方法，用来通过机器人 QQ 或 OneBot 实例的 ID 获取 OneBotV11对象。

参数：`$robot_id`，必填。

```php
$r = OneBotV11::get(123456); 
$r->sendPrivateMsg(55555, "hello");
```

### OneBotV11::getRandom()

静态方法，随机获取一个连接到框架的机器人（多个机器人实例连接到框架时适用）。

如果框架没有连接到任何机器人实例，则会抛出一个异常：`ZM\Exception\RobotNotFoundException`。

```php
try {
	$bot = OneBotV11::getRandom();
	$bot->sendPrivateMsg(55555, "foo");
} catch (\ZM\Exception\RobotNotFoundException $e) {
    echo "还没有机器人连接到框架！\n";
}
```

### OneBotV11::getAllRobot()

获取所有连接到框架的机器人的 OneBotV11 对象。

返回值：`OneBotV11[]`。

```php
$all = OneBotV11::getAllRobot();
foreach($all as $v) {
    $v->sendPrivateMsg(55555, "机器人轮流给一个人发消息啦！");
}
```

### __construct()

构造方法。

参数：`$connection`：炸毛框架内部的连接对象，必填参数。

```php
//从上下文获取 Websocket 连接对象
$conn = ctx()->getConnection();
$bot = new OneBotV11($conn);
```

## 返回结果处理

因为框架的机器人是兼容 OneBot 标准的（原 CQHTTP），所以每次接收发送 API 请求的结果都是大体一样的结构。我们以 `sendPrivateMsg()` 为例，因为发送出去的每一条消息都会在 OneBot 实例（如 CQHTTP 插件、go-cqhttp 等）中对应一个消息 ID，以供我们核查消息和后续撤回等操作需要。

```php
$bot = OneBotV11::get("123456"); // 机器人QQ号
$obj = $bot->sendGroupMsg("234567", "你好");
echo json_encode($obj, 128|256);
```

```json
// 输出结果
{
    "status": "ok",
    "retcode": 0,
    "data": {
        "message_id": 1243
    }
}
```

如上，`$obj` 就是我们的回包内容，我们通过调用 `sendPrivateMsg()` 这个 API 后，会拿到机器人发送此条消息的消息 ID，然后可以通过它来进行其他操作（例如撤回）。

```php
$result = $bot->deleteMsg($obj["data"]["message_id"]);
vardump($result["retcode"]); //如果成功撤回，输出 int(0)
```

### 状态码和 Data

状态码一般情况成功都是 0 或者 200，在过去，炸毛框架兼容 CQHTTP 插件时，错误码的标准按照 CYKU 给出的标准编写，不同的 OneBot 标准的实现，可能有不同的数值，需要根据你对接的机器人客户端进行适配。

结果中返回的 `data` 字段根据下方不同 API 的调用而不同，具体查看每个 API 写明的 `响应数据` 表格。

### response 表

| 字段名    | 数据类型 | 默认值     | 说明                    |
| --------- | -------- | ---------- | ----------------------- |
| `status`  | String   | "ok"       | 状态码说明              |
| `retcode` | number   | 0          | 返回状态码              |
| `data`    | array    | 见 data 表 | 根据不同的 API 返回不同 |

## 机器人 API 方法

### sendPrivateMsg()

参数

| 字段名        | 数据类型 | 默认值  | 说明                                                         |
| ------------- | -------- | ------- | ------------------------------------------------------------ |
| `user_id`     | number   | -       | 对方 QQ 号                                                   |
| `message`     | message  | -       | 要发送的内容                                                 |
| `auto_escape` | boolean  | `false` | 消息内容是否作为纯文本发送（即不解析 CQ 码），只在 `message` 字段是字符串时有效 |

响应数据

| 字段名       | 数据类型       | 说明    |
| ------------ | -------------- | ------- |
| `message_id` | number (int32) | 消息 ID |

例子

=== "代码"

	```php
	$bot = OneBotV11::get(123456); // 123456是你的机器人QQ
	$bot->sendPrivateMsg("627577391", "你好啊！你好你好！");
	```

=== "效果"

	<chat-box>
	( 你好啊！你好你好！
	</chat-box>


### sendGroupMsg()

发送群组消息。

参数

| 字段名        | 数据类型 | 默认值  | 说明                                                         |
| ------------- | -------- | ------- | ------------------------------------------------------------ |
| `group_id`    | number   | -       | 群号                                                         |
| `message`     | message  | -       | 要发送的内容                                                 |
| `auto_escape` | boolean  | `false` | 消息内容是否作为纯文本发送（即不解析 CQ 码），只在 `message` 字段是字符串时有效 |

响应数据

| 字段名       | 数据类型       | 说明    |
| ------------ | -------------- | ------- |
| `message_id` | number (int32) | 消息 ID |

### sendMsg()

发送消息。

参数

| 字段名         | 数据类型 | 默认值  | 说明                                                         |
| -------------- | -------- | ------- | ------------------------------------------------------------ |
| `message_type` | string   | -       | 消息类型，支持 `private`、`group`、`discuss`，分别对应私聊、群组、讨论组，如不传入，则根据传入的 `*_id` 参数判断 |
| `target_id`    | number   | -       | 目标号码，如 QQ 号，群号，讨论组号                           |
| `message`      | message  | -       | 要发送的内容                                                 |
| `auto_escape`  | boolean  | `false` | 消息内容是否作为纯文本发送（即不解析 CQ 码），只在 `message` 字段是字符串时有效 |

响应数据

| 字段名       | 数据类型       | 说明    |
| ------------ | -------------- | ------- |
| `message_id` | number (int32) | 消息 ID |

### deleteMsg()

撤回消息。

参数

| 字段名       | 数据类型       | 默认值 | 说明    |
| ------------ | -------------- | ------ | ------- |
| `message_id` | number (int32) | -      | 消息 ID |

响应数据：无

### getMsg()

获取消息。

参数

| 字段名    | 数据类型 | 默认值 | 说明                             |
| --------- | -------- | ------ | -------------------------------- |
| `message_id` | number   | -      | 消息 ID                       |

响应数据

| 字段名         | 数据类型       | 说明                                                         |
| -------------- | -------------- | ------------------------------------------------------------ |
| `time`         | number (int32) | 发送时间                                                     |
| `message_type` | string         | 消息类型，同 [消息事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/message.md) |
| `message_id`   | number (int32) | 消息 ID                                                      |
| `real_id`      | number (int32) | 消息真实 ID                                                  |
| `sender`       | object         | 发送人信息，同 [消息事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/message.md) |
| `message`      | message        | 消息内容                                                     |

### getForwardMsg()

获取合并转发消息。

参数

| 字段名 | 数据类型 | 说明        |
| ------ | -------- | ----------- |
| `id`   | string   | 合并转发 ID |

响应数据

| 字段名    | 类型    | 说明                                                         |
| --------- | ------- | ------------------------------------------------------------ |
| `message` | message | 消息内容，使用 [消息的数组格式](https://github.com/howmanybots/onebot/blob/master/v11/specs/message/array.md) 表示，数组中的消息段全部为 [`node` 消息段](https://github.com/howmanybots/onebot/blob/master/v11/specs/message/segment.md#合并转发自定义节点) |

### sendLike()

发送好友赞。

参数

| 字段名    | 数据类型 | 默认值 | 说明                             |
| --------- | -------- | ------ | -------------------------------- |
| `user_id` | number   | -      | 对方 QQ 号                       |
| `times`   | number   | 1      | 赞的次数，每个好友每天最多 10 次 |

响应数据

无

### setGroupKick()

群组踢人。

参数

| 字段名               | 数据类型 | 默认值  | 说明               |
| -------------------- | -------- | ------- | ------------------ |
| `group_id`           | number   | -       | 群号               |
| `user_id`            | number   | -       | 要踢的 QQ 号       |
| `reject_add_request` | boolean  | `false` | 拒绝此人的加群请求 |

响应数据：无

### setGroupBan()

群组单人禁言。

参数

| 字段名     | 数据类型 | 默认值    | 说明                             |
| ---------- | -------- | --------- | -------------------------------- |
| `group_id` | number   | -         | 群号                             |
| `user_id`  | number   | -         | 要禁言的 QQ 号                   |
| `duration` | number   | `30 * 60` | 禁言时长，单位秒，0 表示取消禁言 |

响应数据：无

### setGroupAnonymousBan()

群组匿名用户禁言。

参数

| 字段名              | 数据类型         | 默认值    | 说明                                                         |
| ------------------- | ---------------- | --------- | ------------------------------------------------------------ |
| `group_id`          | number           | -         | 群号                                                         |
| `anonymous_or_flag` | object 或 string | -         | 要禁言的匿名用户对象（群消息上报的 `anonymous` 字段）或用户的 flag |
| `duration`          | number           | `30 * 60` | 禁言时长，单位秒，无法取消匿名用户禁言                       |

上面的 `anonymous_or_flag` 两者任选其一传入即可。

响应数据：无

### setGroupWholeBan()

群组全员禁言

参数

| 字段名     | 数据类型 | 默认值 | 说明     |
| ---------- | -------- | ------ | -------- |
| `group_id` | number   | -      | 群号     |
| `enable`   | boolean  | `true` | 是否禁言 |

响应数据：无

### setGroupAdmin()

群组设置管理员

参数

| 字段名     | 数据类型 | 默认值 | 说明                      |
| ---------- | -------- | ------ | ------------------------- |
| `group_id` | number   | -      | 群号                      |
| `user_id`  | number   | -      | 要设置管理员的 QQ 号      |
| `enable`   | boolean  | `true` | true 为设置，false 为取消 |

响应数据：无

### setGroupAnonymous()

群组匿名

参数

| 字段名     | 数据类型 | 默认值 | 说明             |
| ---------- | -------- | ------ | ---------------- |
| `group_id` | number   | -      | 群号             |
| `enable`   | boolean  | `true` | 是否允许匿名聊天 |

响应数据：无

### setGroupCard()

设置群名片（群备注）

参数

| 字段名     | 数据类型 | 默认值 | 说明                                     |
| ---------- | -------- | ------ | ---------------------------------------- |
| `group_id` | number   | -      | 群号                                     |
| `user_id`  | number   | -      | 要设置的 QQ 号                           |
| `card`     | string   | 空     | 群名片内容，不填或空字符串表示删除群名片 |

响应数据：无

### setGroupName()

设置群名。

参数

| 字段名       | 数据类型       | 说明   |
| ------------ | -------------- | ------ |
| `group_id`   | number (int64) | 群号   |
| `group_name` | string         | 新群名 |

响应数据：无

### setGroupLeave()

退出群组

参数

| 字段名       | 数据类型 | 默认值  | 说明                                                     |
| ------------ | -------- | ------- | -------------------------------------------------------- |
| `group_id`   | number   | -       | 群号                                                     |
| `is_dismiss` | boolean  | `false` | 是否解散，如果登录号是群主，则仅在此项为 true 时能够解散 |

响应数据：无

### setGroupSpecialTitle()

设置群组专属头衔

参数

| 字段名          | 数据类型 | 默认值 | 说明                                                         |
| --------------- | -------- | ------ | ------------------------------------------------------------ |
| `group_id`      | number   | -      | 群号                                                         |
| `user_id`       | number   | -      | 要设置的 QQ 号                                               |
| `special_title` | string   | 空     | 专属头衔，不填或空字符串表示删除专属头衔                     |
| `duration`      | number   | `-1`   | 专属头衔有效期，单位秒，-1 表示永久，不过此项似乎没有效果，可能是只有某些特殊的时间长度有效，有待测试 |

响应数据：无

### setFriendAddRequest()

处理加好友请求

参数

| 字段名    | 数据类型 | 默认值 | 说明                                      |
| --------- | -------- | ------ | ----------------------------------------- |
| `flag`    | string   | -      | 加好友请求的 flag（需从上报的数据中获得） |
| `approve` | boolean  | `true` | 是否同意请求                              |
| `remark`  | string   | 空     | 添加后的好友备注（仅在同意时有效）        |

响应数据：无

### setGroupAddRequest()

处理加群请求 / 邀请

参数

| 字段名     | 数据类型 | 默认值 | 说明                                                         |
| ---------- | -------- | ------ | ------------------------------------------------------------ |
| `flag`     | string   | -      | 加群请求的 flag（需从上报的数据中获得）                      |
| `sub_type` | string   | -      | `add` 或 `invite`，请求类型（需要和上报消息中的 `sub_type` 字段相符） |
| `approve`  | boolean  | `true` | 是否同意请求／邀请                                           |
| `reason`   | string   | 空     | 拒绝理由（仅在拒绝时有效）                                   |

响应数据无

### getLoginInfo()

获取登录号信息

参数：无

响应数据

| 字段名     | 数据类型       | 说明    |
| ---------- | -------------- | ------- |
| `user_id`  | number (int64) | QQ 号   |
| `nickname` | string         | QQ 昵称 |

### getStrangerInfo()

获取陌生人信息

参数

| 字段名     | 数据类型 | 默认值  | 说明                                                 |
| ---------- | -------- | ------- | ---------------------------------------------------- |
| `user_id`  | number   | -       | QQ 号                                                |
| `no_cache` | boolean  | `false` | 是否不使用缓存（使用缓存可能更新不及时，但响应更快） |

响应数据

| 字段名     | 数据类型       | 说明                                  |
| ---------- | -------------- | ------------------------------------- |
| `user_id`  | number (int64) | QQ 号                                 |
| `nickname` | string         | 昵称                                  |
| `sex`      | string         | 性别，`male` 或 `female` 或 `unknown` |
| `age`      | number (int32) | 年龄                                  |

### getFriendList()

获取好友列表

参数：无

响应数据

响应内容为 JSON 数组，每个元素如下：

| 字段名     | 数据类型       | 说明   |
| ---------- | -------------- | ------ |
| `user_id`  | number (int64) | QQ 号  |
| `nickname` | string         | 昵称   |
| `remark`   | string         | 备注名 |

### getGroupInfo()

获取群信息

参数

| 字段名     | 数据类型 | 默认值  | 说明                                                 |
| ---------- | -------- | ------- | ---------------------------------------------------- |
| `group_id` | number   | -       | 群号                                                 |
| `no_cache` | boolean  | `false` | 是否不使用缓存（使用缓存可能更新不及时，但响应更快） |

响应数据

| 字段名             | 数据类型       | 说明                 |
| ------------------ | -------------- | -------------------- |
| `group_id`         | number (int64) | 群号                 |
| `group_name`       | string         | 群名称               |
| `member_count`     | number (int32) | 成员数               |
| `max_member_count` | number (int32) | 最大成员数（群容量） |

### getGroupList()

获取群列表

参数：无

响应数据

响应内容为 JSON 数组，每个元素如下：

| 字段名       | 数据类型       | 说明   |
| ------------ | -------------- | ------ |
| `group_id`   | number (int64) | 群号   |
| `group_name` | string         | 群名称 |

### getGroupMemberInfo()

获取群成员信息

参数

| 字段名     | 数据类型 | 默认值  | 说明                                                 |
| ---------- | -------- | ------- | ---------------------------------------------------- |
| `group_id` | number   | -       | 群号                                                 |
| `user_id`  | number   | -       | QQ 号                                                |
| `no_cache` | boolean  | `false` | 是否不使用缓存（使用缓存可能更新不及时，但响应更快） |

响应数据

| 字段名              | 数据类型       | 说明                                  |
| ------------------- | -------------- | ------------------------------------- |
| `group_id`          | number (int64) | 群号                                  |
| `user_id`           | number (int64) | QQ 号                                 |
| `nickname`          | string         | 昵称                                  |
| `card`              | string         | 群名片／备注                          |
| `sex`               | string         | 性别，`male` 或 `female` 或 `unknown` |
| `age`               | number (int32) | 年龄                                  |
| `area`              | string         | 地区                                  |
| `join_time`         | number (int32) | 加群时间戳                            |
| `last_sent_time`    | number (int32) | 最后发言时间戳                        |
| `level`             | string         | 成员等级                              |
| `role`              | string         | 角色，`owner` 或 `admin` 或 `member`  |
| `unfriendly`        | boolean        | 是否不良记录成员                      |
| `title`             | string         | 专属头衔                              |
| `title_expire_time` | number (int32) | 专属头衔过期时间戳                    |
| `card_changeable`   | boolean        | 是否允许修改群名片                    |

### getGroupMemberList()

获取群成员列表

参数

| 字段名     | 数据类型 | 默认值 | 说明 |
| ---------- | -------- | ------ | ---- |
| `group_id` | number   | -      | 群号 |

响应数据

响应内容为 JSON 数组，每个元素的内容和上面的 `/get_group_member_info` 接口相同，但对于同一个群组的同一个成员，获取列表时和获取单独的成员信息时，某些字段可能有所不同，例如 `area`、`title` 等字段在获取列表时无法获得，具体应以单独的成员信息为准。

### getGroupHonorInfo()

获取群荣誉信息。

参数

| 字段名     | 数据类型       | 默认值 | 说明                                                         |
| ---------- | -------------- | ------ | ------------------------------------------------------------ |
| `group_id` | number (int64) | -      | 群号                                                         |
| `type`     | string         | -      | 要获取的群荣誉类型，可传入 `talkative` `performer` `legend` `strong_newbie` `emotion` 以分别获取单个类型的群荣誉数据，或传入 `all` 获取所有数据 |

响应数据

| 字段名               | 数据类型       | 说明                                                       |
| -------------------- | -------------- | ---------------------------------------------------------- |
| `group_id`           | number (int64) | 群号                                                       |
| `current_talkative`  | object         | 当前龙王，仅 `type` 为 `talkative` 或 `all` 时有数据       |
| `talkative_list`     | array          | 历史龙王，仅 `type` 为 `talkative` 或 `all` 时有数据       |
| `performer_list`     | array          | 群聊之火，仅 `type` 为 `performer` 或 `all` 时有数据       |
| `legend_list`        | array          | 群聊炽焰，仅 `type` 为 `legend` 或 `all` 时有数据          |
| `strong_newbie_list` | array          | 冒尖小春笋，仅 `type` 为 `strong_newbie` 或 `all` 时有数据 |
| `emotion_list`       | array          | 快乐之源，仅 `type` 为 `emotion` 或 `all` 时有数据         |

其中 `current_talkative` 字段的内容如下：

| 字段名      | 数据类型       | 说明     |
| ----------- | -------------- | -------- |
| `user_id`   | number (int64) | QQ 号    |
| `nickname`  | string         | 昵称     |
| `avatar`    | string         | 头像 URL |
| `day_count` | number (int32) | 持续天数 |

其它各 `*_list` 的每个元素是一个 JSON 对象，内容如下：

| 字段名        | 数据类型       | 说明     |
| ------------- | -------------- | -------- |
| `user_id`     | number (int64) | QQ 号    |
| `nickname`    | string         | 昵称     |
| `avatar`      | string         | 头像 URL |
| `description` | string         | 荣誉描述 |

### getCookies()

获取 Cookies。

!!! warning "注意"

	目前开源的 mirai 为底层的机器人客户端均不支持获取 Cookies 和 CSRF Token，包括 go-cqhttp。


参数

| 字段名   | 数据类型 | 默认值 | 说明                    |
| -------- | -------- | ------ | ----------------------- |
| `domain` | string   | 空     | 需要获取 cookies 的域名 |

响应数据

| 字段名    | 数据类型 | 说明    |
| --------- | -------- | ------- |
| `cookies` | string   | Cookies |

### getCsrfToken()

获取 CSRF Token

参数：无

响应数据

| 字段名  | 数据类型       | 说明       |
| ------- | -------------- | ---------- |
| `token` | number (int32) | CSRF Token |

### getCredentials()

获取 QQ 相关接口凭证，即上面两个合并。

参数

| 字段名   | 数据类型 | 默认值 | 说明                    |
| -------- | -------- | ------ | ----------------------- |
| `domain` | string   | 空     | 需要获取 cookies 的域名 |

响应数据

| 字段名       | 数据类型       | 说明       |
| ------------ | -------------- | ---------- |
| `cookies`    | string         | Cookies    |
| `csrf_token` | number (int32) | CSRF Token |

### getRecord()

获取语音。其实并不是真的获取语音，而是转换语音到指定的格式。

> **提示**：要使用此接口，通常需要安装 ffmpeg，请参考 OneBot 实现的相关说明。

参数

| 字段名       | 数据类型 | 默认值 | 说明                                                         |
| ------------ | -------- | ------ | ------------------------------------------------------------ |
| `file`       | string   | -      | 收到的语音文件名（CQ 码的 `file` 参数），如 `0B38145AA44505000B38145AA4450500.silk` |
| `out_format` | string   | -      | 要转换到的格式，目前支持 `mp3`、`amr`、`wma`、`m4a`、`spx`、`ogg`、`wav`、`flac` |

响应数据

| 字段名 | 数据类型 | 说明                                                         |
| ------ | -------- | ------------------------------------------------------------ |
| `file` | string   | 转换后的语音文件路径，如 `/home/somebody/cqhttp/data/record/0B38145AA44505000B38145AA4450500.mp3` |

### getImage()

获取图片。

参数

| 字段名 | 数据类型 | 默认值 | 说明                                                         |
| ------ | -------- | ------ | ------------------------------------------------------------ |
| `file` | string   | -      | 收到的图片文件名（CQ 码的 `file` 参数），如 `6B4DE3DFD1BD271E3297859D41C530F5.jpg` |

响应数据

| 字段名 | 数据类型 | 说明                                                         |
| ------ | -------- | ------------------------------------------------------------ |
| `file` | string   | 下载后的图片文件路径，如 `/home/somebody/cqhttp/data/image/6B4DE3DFD1BD271E3297859D41C530F5.jpg` |

### canSendImage()

检查是否可以发送图片。

参数：无

响应数据

| 字段名 | 数据类型 | 说明   |
| ------ | -------- | ------ |
| `yes`  | boolean  | 是或否 |

### canSendRecord()

检查是否可以发送语音，返回同上。

### getStatus()

获取插件运行状态。

参数：无

响应数据

| 字段名   | 数据类型 | 说明                                                     |
| -------- | -------- | -------------------------------------------------------- |
| `online` | boolean  | 当前 QQ 在线，`null` 表示无法查询到在线状态              |
| `good`   | boolean  | 状态符合预期，意味着各模块正常运行、功能正常，且 QQ 在线 |
| ......   | -        | OneBot 实例自行添加的其他内容                            |

通常情况下建议只使用 `online` 和 `good` 这两个字段来判断运行状态，因为根据 OneBot 实现的不同，其它字段可能完全不同。

### getVersionInfo()

获取版本信息

响应数据

| 字段名             | 数据类型 | 说明                          |
| ------------------ | -------- | ----------------------------- |
| `app_name`         | string   | 应用标识，如 `mirai-native`   |
| `app_version`      | string   | 应用版本，如 `1.2.3`          |
| `protocol_version` | string   | OneBot 标准版本，如 `v11`     |
| ……                 | -        | OneBot 实现自行添加的其它内容 |

### setRestartPlugin()

重启 OneBot 客户端

由于重启 OneBot 实现同时需要重启 API 服务，这意味着当前的 API 请求会被中断，因此需要异步地重启，接口返回的 `status` 是 `async`。

参数

| 字段名  | 数据类型 | 默认值 | 说明                                                         |
| ------- | -------- | ------ | ------------------------------------------------------------ |
| `delay` | number   | `0`    | 要延迟的毫秒数，如果默认情况下无法重启，可以尝试设置延迟为 2000 左右 |

响应数据：无

### cleanCache()

清理 OneBot 客户端的缓存。

参数：无

响应数据：无

### callExtendedAPI() （扩充 API）

用来调用 OneBot 标准之外扩展出来的自定义 API。

使用不同 OneBot 客户端时，可能有一些 API 不在上方的 OneBot 标准里，这时可以使用此方法进行额外调用。

参数

| 字段名   | 数据类型 | 默认值 |
| -------- | -------- | ------ |
| `action` | string   | 必填   |
| `params` | array    | `[]`   |

例子

```php
$result = $bot->callExtendedAPI("get_group_root_files", ["group_id" => 123456]); 
//这里以 go-cqhttp 扩展的一个获取群文件的 API 为例
var_dump($result["data"]); 
// 输出群文件列表
```

