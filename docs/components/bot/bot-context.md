# OneBot 机器人上下文

在你使用炸毛框架为你的机器人编写逻辑和使用插件时，围绕机器人的核心处理方式为机器人上下文。
机器人上下文可获取当前机器人事件的其他参数、发起机器人动作请求、互动询问消息等。

BotContext 为上下文对象，例如下方的 `#[\BotCommand]` 事件：

```php
#[\BotCommand(match: 'SOS')]
public function sos(\BotContext $ctx)
{
    $ctx->reply('不许求救！');
}
```

关于 BotContext 机器人上下文，如果你不喜欢上方通过参数绑定的依赖注入（DI）形式获取，还可以在相关事件内使用全局函数 `bot()` 获取：

```php
#[\BotCommand(match: 'SOS')]
public function sos()
{
    bot()->reply('不许求救！');
}
```

## reply() - 快速回复消息

可能是你在编写插件时最常用的方法，用于快速回复消息。

- 定义：`reply($message, int $reply_mode = ZM_REPLY_NONE)`
- 返回：`ActionResponse|bool`

参数说明：

- `$message`：消息内容，可以是字符串或数组，如果是数组，会验证是否为标准的消息段格式。有关消息段格式的说明，见 [消息段](message-segment.md)。
- `$reply_mode`：回复模式，可选值为 `ZM_REPLY_NONE`、`ZM_REPLY_MENTION`、`ZM_REPLY_QUOTE`，默认为 `ZM_REPLY_NONE`。

回复模式说明：

- `ZM_REPLY_NONE`：仅回复文本本身，直接发送消息。
- `ZM_REPLY_MENTION`：回复并 @ 消息发送者。
- `ZM_REPLY_QUOTE`：回复并引用消息。

其中 `$message` 和下方的 `sendMessage()` 内的 `$message` 参数格式一致，可参考下方。
`$reply_mode` 可以通过 `ZM_REPLY_MENTION` 和 `ZM_REPLY_QUOTE` 两个常量来设置，例如：

```php
#[BotCommand(match: '测试at')]
public function testAt(\BotContext $ctx)
{
    $ctx->reply('测试at回复完成', ZM_REPLY_MENTION);
}

#[BotCommand(match: '测试引用')]
public function testDefault(\BotContext $ctx)
{
    $ctx->reply('测试引用回复完成', ZM_REPLY_QUOTE);
}
```

<chat-box :my-chats="[
{type:0,content:'测试at'},
{type:1,content:'@123456 测试at回复完成'},
{type:0,content:'测试引用'},
{type:4,quote:'测试引用',content:'测试引用回复完成'},
]" />

::: warning 注意

`reply()` 只能运用在 `BotCommand`、`BotEvent` 的回调事件中，如果你想在其他事件中使用，可以使用 `sendMessage()` 方法。

:::

## sendMessage() - 发送一条机器人消息

作为聊天机器人的最主要功能，`sendMessage()` 方法可以发送一条消息。

- 定义：`sendMessage($message, string $detail_type, array $params = [])`
- 返回：`ActionResponse|bool`

参数说明：

- `$message`：消息内容，可以是字符串或数组，如果是数组，会验证是否为标准的消息段格式。有关消息段格式的说明，见 [消息段](message-segment.md)。
- `$detail_type`：消息类型，可选值为 `private`、`group`、`channel`，分别表示私聊、群聊、频道。如果你的机器人实现端有扩展，也可以使用自定义的消息类型。
- `$params`：可选参数，用于指定消息的其他参数，例如 `group_id`、`user_id` 等。
- `ActionResponse|false`：如果发送成功，返回一个 `ActionResponse` 对象，否则返回 `false`。

::: tip 提示

如果你的 PHP 没有安装 Swoole 扩展且 PHP 版本低于 8.1，那么将无法返回 ActionResponse，只能返回 `true` 或 `false`。

:::

示例：

```php
// 发送一条私聊消息
$response = $ctx->sendMessage('你好', 'private', ['user_id' => '123456']);
if ($response instanceof \ActionResponse) {
    // 发送成功
    $response->data['message_id']; // 获取发送的消息 ID
} else {
    // 发送失败
}

// 发送一条多媒体消息，例如图片
$ctx->sendMessage([MessageSegment::image(file_id: '7815696ecbf1c96e6894b779456d330e')]);
```

## sendAction() - 发送一个机器人动作

`sendAction()` 方法可以发送一个机器人动作，动作的格式和支持的动作列表详见 [OneBot12 - 接口定义](https://12.onebot.dev/interface/)。

- 定义：`sendAction(string $action, array $params = [], ?array $self = null)`
- 返回：`ActionResponse|bool`

参数说明：

- `$action`：动作名称，例如 `send_message`、`get_status` 等。
- `$params`：动作参数，例如 `message`、`user_id` 等。
- `$self`：可选参数，用于指定动作的机器人平台和对应机器人自身 ID，如果不指定，将使用当前上下文机器人的 ID。

`$self` 参数仅在连接到炸毛框架的 OneBot 12 实现端需要时有效，例如部分机器人实现端支持多机器人占用一个连接，这时必须指定 `self.user_id` 和 `self.platform` 参数。

示例：

```php
// 从实现端获取机器人状态，写入终端日志
$response = $ctx->sendAction('get_status');
if ($response instanceof \ActionResponse) {
    // 获取成功
    $good = $response->data['good']; // 获取机器人在线状态
    logger()->info('机器人状态：' . ($good ? '在线' : '离线'));
} else {
    logger()->error('获取机器人状态失败');
}
```

::: tip 提示

部分 OneBot 12 实现端支持了较多 OneBot 12 标准以外的扩展动作，这里也可以使用 `sendAction()` 方法发送这些扩展动作。
但动作列表需要自行寻找对应实现端的文档进行查询。

:::

## prompt() - 等待一条消息回复

`prompt()` 方法用处是等待一条用户的回复消息，它会阻塞当前协程，直到用户回复消息或超时。

> 此方法仅在 `BotCommand` 或 `BotEvent` 内 `type` 为 `message` 的上下文中有效，且仅可在协程环境可用时使用。

- 定义：`prompt($prompt = '', int $timeout = 600, string $timeout_prompt = '', bool $return_string = false, int $option = ZM_PROMPT_NONE)`
- 返回：`MessageSegment[]|string`

参数说明：

- `$prompt`：可选参数，用于指定等待回复时发送的提示消息，如果不指定，将不发送提示消息。
- `$timeout`：可选参数，用于指定等待回复的超时时间，单位为秒，如果不指定，将使用默认的 600 秒。
- `$timeout_prompt`：可选参数，用于指定等待回复超时时发送的提示消息，如果不指定，将不发送提示消息。
- `$return_string`：可选参数，用于指定是否返回字符串形式的消息，如果不指定，将返回消息段数组。
- `$option`：可选参数，用于设置 prompt 等待回复提示语句和超时语句的额外选项

返回说明：

`$return_string` 默认为 false，即等待消息回复后拿到的消息返回格式为消息段数组格式。

该函数只会在成功时候返回，如果超时，会抛出一个 `WaitTimeoutException` 异常，但会被 OneBot 处理器捕获并回复超时消息，使用此功能的开发者无需捕获此异常。

额外选项 `$option` 说明：

- `ZM_PROMPT_NONE`：不附加任何特性，直接回复原内容（默认）。
- `ZM_PROMPT_MENTION_USER`：在发送 `$prompt` 消息时，在消息前添加一个 at 该用户。（如果 `$prompt` 为空则该参数无效）
- `ZM_PROMPT_QUOTE_USER`：在发送 `$prompt` 消息时，引用当前上下文绑定的那条用户消息。（如果 `$prompt` 为空则该参数无效）
- `ZM_PROMPT_TIMEOUT_MENTION_USER`：在询问参数超时时，如果超时的消息不为空则在超时的消息前添加一个 at 该用户。
- `ZM_PROMPT_TIMEOUT_QUOTE_SELF`：在询问参数超时时，如果 `$timeout_prompt` 和 `$prompt` 均不为空，则在发送超时提示语时引用自己发送的 `$prompt` 提示语。
- `ZM_PROMPT_TIMEOUT_QUOTE_USER`：在询问参数超时时，如果超时的消息不为空则引用用户最开始触发该注解的消息。

示例：

```php
#[\BotCommand(match: '测试段')]
public function testSegment(\BotContext $ctx)
{
    // 等待用户回复一条消息
    $reply = $ctx->prompt('请回复一条消息');
    // 如果用户回复了消息，那么 reply 将是一个消息段数组
    // 如果用户没有回复消息，超时了，那下方的代码不会被执行，此处的事件流程将强制中断
    $ctx->reply(['你回复了：', ...$reply]);
}

#[\BotCommand(match: '测试字符串')]
public function testString(\BotContext $ctx)
{
    // 等待用户回复一条消息
    $reply = $ctx->prompt('请回复一条消息', 600, '你超时了', true);
    // 如果用户回复了消息，那么 reply 将是一个字符串
    // 如果用户没有回复消息，超时了，那下方的代码不会被执行，此处的事件流程将强制中断
    $ctx->reply('你回复了：' . $reply);
    $reply2 = $ctx->prompt('请再回复一条消息', 30, '你又超时了', true, ZM_PROMPT_TIMEOUT_QUOTE_SELF);
}
```

<chat-box :my-chats="[
{type:0,content:'测试段'},
{type:1,content:'请回复一条消息'},
{type:0,content:'test'},
{type:1,content:'你回复了：test'},
{type:0,content:'测试字符串'},
{type:1,content:'请回复一条消息'},
{type:2,content:'等待 600 秒以上'},
{type:1,content:'你超时了'},
{type:0,content:'测试字符串'},
{type:1,content:'请回复一条消息'},
{type:0,content:'abab'},
{type:1,content:'你回复了：abab'},
{type:1,content:'请再回复一条消息'},
{type:2,content:'等待 30 秒以上'},
{type:4,quote:'请再回复一条消息',content:'你又超时了'},
]" />

## hasReplied() - 检查是否已回复

`hasReplied()` 方法用处是检查当前事件是否已经回复过消息，如果已经回复过消息（即调用过 `reply()`），那么这里将返回 `true`，否则返回 `false`。

- 定义：`hasReplied()`
- 返回：`bool`

示例：

```php
#[\BotCommand(match: '测试消息')]
public function testMsg(\BotContext $ctx)
{
    $ctx->reply('这是一条消息');
    // 检查是否已经回复过消息
    if ($ctx->hasReplied()) {
        $ctx->reply('已经回复过消息了');
    } else {
        $ctx->reply('还没有回复过消息');
    }
}
```

<chat-box :my-chats="[
{type:0,content:'测试消息'},
{type:1,content:'这是一条消息'},
{type:1,content:'已经回复过消息了'},
]" />

## getBot() - 获取其他机器人上下文对象

`getBot()` 方法用处是获取其他机器人的上下文，这里的“其他机器人”是指同时连接到框架的其他 OneBot 12 实现端的上下文。

例如你使用了两个 Walle-Q 连接到框架，机器人 QQ 号分别为 123 和 456，你在 123 机器人的上下文中要获取 456 机器人的上下文：

```php
$another_ctx = $ctx->getBot(bot_id: '456', platform: 'qq');
```

- 定义：`getBot(string $bot_id, string $platform = '')`
- 返回：`\BotContext`

## getParam() - 获取 BotCommand 注解解析出来的参数

`getParam()` 方法用处是获取 BotCommand 和 CommandArgument 注解解析出来的参数，这里的“参数”是指 BotCommand 注解的 `param` 属性。

- 定义：`getParam(string $name)`
- 返回：`MessageSegment[]|string|null`

参数 `$name` 是参数名，如果参数不存在，将返回 `null`。

由 CommandArgument 注解解析出来的参数，且 `required` 为 `true`，则对应名字的 param 必定会存在，可以通过对应 CommandArgument 绑定的名字来获取，例如：

```php
#[\BotCommand(match: '测试参数')]
#[\CommandArgument(name: 'param1', type: 'string', required: true)]
public function testParam(\BotContext $ctx)
{
    $ctx->reply(['参数1：', ...$ctx->getParam('param1')]);
}
```

<chat-box :my-chats="[
{type:0,content:'测试参数'},
{type:1,content:'请输入param1'},
{type:0,content:'test'},
{type:1,content:'参数1：test'},
{type:0,content:'测试参数 test123'},
{type:1,content:'参数1：test123'},
]" />

如果你没有使用 CommandArgument 绑定参数或想获取更多参数，可以使用 `getParam('.unnamed')` 方法，这样会返回所有未绑定的参数：

```php
#[\BotCommand(match: '测试参数')]
#[\CommandArgument(name: 'param1', type: 'string', required: true)]
public function testParam(\BotContext $ctx)
{
    $ctx->reply(['参数1：', ...$ctx->getParam('param1')]);
    $ctx->reply('未命名参数列表：[' . implode(', ', $ctx->getParam('.unnamed')) . ']');
}
```

<chat-box :my-chats="[
{type:0,content:'测试参数 abc def ghhaha   你好'},
{type:1,content:'参数1：abc'},
{type:1,content:'未命名参数列表：[def, ghhaha, 你好]'},
]" />

## getParamString() - 获取 BotCommand 注解解析出来的参数字符串

`getParamString()` 功能和 `getParam()` 完全一样，参数也一模一样，不一样的地方在于这个方法返回的参数都是字符串，而 `getParam()` 返回的是 MessageSegment 数组。

一般用于不在意用户返回的其他富文本参数，只需要字符串参数的情况。

- 定义：`getParamString(string $name)`
- 返回：`string|null`

## getParams() - 获取 BotCommand 注解解析出来的所有参数

`getParams()` 方法用处是获取 BotCommand 注解解析出来的所有参数，这里的“参数”是指 BotCommand 注解的 `param` 属性。

- 定义：`getParams()`
- 返回：`array`

```php
#[\BotCommand(match: '测试参数')]
#[\CommandArgument(name: 'param1', type: 'string', required: true)]
public function testParam(\BotContext $ctx)
{
    logger()->info(json_encode($ctx->getParams(), JSON_PRETTY_PRINT));
}
// 假设询问内容为“测试参数 abc def”，返回结果：
/*
{
    "param1":[{"type":"text","data":{"text":"abc"}}],
    ".unnamed":[{"type":"text","data":{"text":"def"}}]
}
*/
```

## getSelf() - 获取当前上下文的机器人信息

- 定义：`getSelf()`
- 返回：`array`

```php
$self = $ctx->getSelf();
// 返回：["user_id" => "123456", "platform" => "qq"]
```
