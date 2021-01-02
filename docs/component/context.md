# 上下文

上下文作为整个框架中最重要的内容之一，请务必理解和完整地阅读此部分！

一个上下文描述了一个事件和所关联的对象的环境。例如：你在处理 HTTP 请求的 `@RequestMapping` 绑定的事件中，你需要获取请求的 HTTP 头和 Cookie，再比如你在处理 QQ 机器人发来的命令 `@CQCommand("随机数")` 的时候，在这个方法内，你需要获取发来的人的 QQ 号码。以上我们将处理以上运行环境的对象叫做上下文。

由于 Swoole 的协程加持，我们利用了协程 ID 绑定对象来进行构造上下文。

以默认的机器人收发消息为例，通过对默认模块的了解，我们可以知道，在绑定 `@CQCommand` 等类似事件后，你可以用上下文获取发来这条消息的人的 QQ 号码：

```php
/**
 * @CQCommand("你好")
 */
public function hello() {
  $user_id = ctx()->getUserId();
  ctx()->reply("你好啊，".$user_id."，很高兴认识你！");
}
```

`context()` 就是获取上下文对象的全局函数，它还有简写：`ctx()`。

当然，上下文中的方法不是每个都能在任何时候使用的。例如 `getUserId()` 你不能在 `@RequestMapping` 注解的函数中使用，因为它不是机器人消息的上下文。下面说明上下文对象的方法中，每个都会说明每个方法可以在哪些事件中使用：

## getServer() - 获取 Server 对象

获取 Swoole WebSocker Server 对象。此对象是 Swoole 的对象，详情见 [Swoole 文档](https://wiki.swoole.com/#/websocket_server)。

可以使用的事件：`@OnSwooleEvent("message")`，`@OnSwooleEvent("open")`，`@OnSwooleEvent("close")`，`@OnStart()` 以及所有 HTTP API 发来的事件：`@CQCommand()`，`@CQMessage()` 等。

## getFrame() - 获取 WS 数据帧

获取 `\Swoole\Websocket\Frame` 对象，此对象是 Swoole 的对象，详情见 [Swoole 文档](https://wiki.swoole.com/#/websocket_server?id=swoolewebsocketframe)。

可以使用的事件：`@OnSwooleEvent("message")` 以及所有 HTTP API 发来的事件：`@CQCommand()`，`@CQMessage()` 等，

## getFd() - 返回 fd 值

获取当前连入 Swoole 服务器的连接文件描述符 ID。返回 int。一般代表连接号，可用来绑定对应链接。

可以使用的事件：所有 **getFrame()** 可以使用的，`@OnSwooleEvent("open")`，`@OnSwooleEvent("close")`

!!! tip "提示"

	值得注意的是，由于机器人客户端和炸毛框架的连接是通过 WebSocket 进行的，而 WebSocket 是长连接，所以同一个机器人一次连接下收发消息所用的连接是同一个，所以 Fd 也是相同的。同理，炸毛框架的内部来区分多个机器人也是通过这一 Fd 进行判定的。

=== "代码"

    ```php
    /**
     * @CQCommand("测试fd")
     */
    public function testfd() {
        ctx()->reply("当前机器人连接的fd是：".ctx()->getFd()"，机器人QQ是：".ctx()->getRobotId());
    }
    ```

=== "效果"

    <chat-box>
    ^ 假设我们和连接55555的机器人的私聊
    ) 测试fd
    ( 当前机器人连接的fd是：1，机器人QQ是：55555
    ^ 假设切到了另一个机器人（66666）的私聊
    ) 测试fd
    ( 当前机器人连接的fd是：2，机器人QQ是：66666
    </chat-box>

## getData() - 获取事件完整数据

返回 CQHTTP 事件上报的原始数据包，已经被解析成数组，可以直接操作。

可以使用的事件：所有 HTTP API 发来的事件：`@CQCommand()`，`@CQMessage()` 等。

```php
/**
 * @CQMessage(user_id=123456)
 */
public function onMessage() {
  $data = ctx()->getData();
  ctx()->reply("消息类型是：" . $data["message_type"]);
}
```

<chat-box>
^ 假设我是QQ为123456的用户，私聊发消息
) 哈咯！！
( 消息类型是：private
</chat-box>

## getRequest() - HTTP 请求对象

返回 `\Swoole\Http\Request` 对象，可在 `@RequestMapping` 中使用，获取 Cookie，请求头，GET 参数什么的。[Swoole 文档](https://wiki.swoole.com/#/http_server?id=httprequest)。

可以使用的事件：`@RequestMapping()`，`@OnSwooleEvent("request")`，`@OnSwooleEvent("open")`。

## getResponse() - HTTP 响应对象

返回 `\Swoole\Http\Response` 对象的增强版，可在 HTTP 请求相关的事件中使用，返回内容和设置 Cookie 什么的。[Swoole 文档](https://wiki.swoole.com/#/http_server?id=httpresponse)。

可以使用的事件：`@RequestMapping()`，`@OnSwooleEvent("request")`。

下面是使用以上两个功能的组合示例：

```php
/**
 * @RequestMapping("/ping")
 */
public function ping() {
  $name = ctx()->getRequest()->get["name"] ?? "unknown";
  ctx()->getResponse()->end("Hello ".$name."!");
}
```

## getConnection() - WS 连接对象

返回此上下文相关联的 WebSocket 连接对象。

可以使用的事件：所有 **getFrame()** 可以使用的都可以使用。

## getCid() - 上下文 ID

返回当前上下文所绑定的协程 ID，此 ID 和 `\Co::getCid()` 返回值一样。

## getRobot() - 获取机器人 API 对象

返回当前上下文关联的机器人 API 调用对象 [ZMRobot](robot-api.md)。

可以使用的事件：所有 HTTP API 发来的事件：`@CQCommand()`，`@CQMessage()` 等。

```php
ctx()->getRobot()->sendPrivateMsg(123456, "发送私聊消息");
```

<chat-box>
^ 正在和机器人聊天
( 发送私聊消息
</chat-box>

## getMessage() - 获取消息

获取 data 数据中的 `message` 消息，用于快速获取用户消息事件的消息内容。

可以使用的事件：`@CQCommand()`，`@CQMessage`，`@CQBefore("message")`，`@CQAfter("message")`

=== "代码"
    ```php
    /**
     * @CQMessage(group_id=33333)
     */
    public function groupRepeat() {
        ctx()->reply(ctx()->getMessage());
    }
    ```

=== "效果"
    <chat-box>
    ^ 现在在群33333内，机器人已经成了复读机
    ) 来世还做复读机！！！
    ( 来世还做复读机！！！
    ) 你不许复读！
    ( 你不许复读！
    </chat-box>

## getUserId() - 获取用户 QQ 号

获取发消息的用户的 QQ 号码。

可以使用的事件：所有 **含有** `user_id` 上报参数的 OneBot 事件。

```php
/**
 * @CQCommand("whoami")
 */
public function whoami() {
    ctx()->reply("你是".ctx()->getUserId()); //返回：你是123456
}
```

## getGroupId() - 获取 QQ 群号

获取发消息来自的 QQ 群号。

可以使用的事件：所有含有 `group_id` 上报参数的 OneBot 事件。

## getMessageType() - 消息类型

获取消息类型，同参数 `message_type`。

可以使用的事件：所有 `post_type` 为 `message` 的响应事件，如 `@CQMessage`，`@CQCommand`。

## getRobotId() - 机器人 QQ 号

获取事件上报的机器人自己的 QQ 号码。

可以使用的事件：所有 OneBot 发来的事件：`@CQCommand()`，`@CQNotice()` 等。

## setMessage() - 设置消息

与 `getMessage()` 对应，用于更改上下文中保存的事件信息，可以用于消息变更和过滤。

## setUserId() - 设置用户 ID

与上同理，更改 `user_id`。

## setGroupId() - 设置群号

与上同理。

## setMessageType() - 设置类型

与上同理，修改消息类型。

## setData() - 设置数据包

与上同理，与 `getData()` 对应，用于更改上下文中的 `data`。

## getCache() - 上下文缓存

获取保存在上下文中的临时缓存变量。当相关联的事件结束后，数据会从内存中被释放。用于同一事件的多个函数中的信息传递。

- 参数：`$key`，缓存变量的键名
- 返回：`mixed`，存入缓存的变量值。

```php
$a = ctx()->getCache("block_continue");
// 如果变量不存在，则返回 null
```

## setCache() - 上下文缓存

与 `getCache()` 对应，是设置内容的。

```php
ctx()->setCache("abc", "asdasd");
$result = ctx()->getCache("abc"); // asdasd
```

## reply() - 快速回复

快速回复当前用户消息内容。

- 参数1：`$msg`，字符串，你要回复的消息内容
- 参数2：`$yield = false`，可选，当为 `true` 时，会协程等待后返回 **消息回复** 的结果，包括 API 状态码、消息 `message_id` 等。

```php
$r = ctx()->reply("我又好了。");
if($r["retcode"] == 0) Console::success("消息发送成功！");
```

## finalReply() - 快速回复

快速回复用户消息，并阻止其他模块接下来继续处理此事件。

参数同 `reply()`。

## waitMessage()

- 参数：`waitMessage($prompt = "", $timeout = 600, $timeout_prompt = "")`
- 用途：等待用户输入消息

`$prompt` 参数为回复用户的文本内容，`$timeout` 是等待用户回复的超时时间(秒)，`$timeout_prompt` 是超时后回复用户的文本。

这个功能可以让开发机器人的代码逻辑和实际贴合，避免回调地狱、拼接参数、上下文脱节等问题，比如下方的示例，可以仅仅用两行代码实现一个问答式的对话过程。

用法示例：

```php
/**
 * @CQCommand("自我介绍")
 */
function yourName(){
    $r = ctx()->waitMessage("你叫啥名字呀？", 600, "你都10分钟不理我了，嘤嘤嘤");
    ctx()->finalReply("好的，可爱的机器人记住你叫 ".$r." 啦！以后多聊天哦！");
}
```

<chat-box>
) 自我介绍
( 你叫啥名字呀？
) jerry
( 好的，可爱的机器人记住你叫 jerry 啦！以后多聊天哦！
) 自我介绍
( 你叫啥名字呀？
^ 10分钟没理机器人
( 你都10分钟不理我了，嘤嘤嘤
</chat-box>

## getArgs()

TODO：还没写到这里，下次更新，今晚太困了。

## copy()

t

## getOption() - 获取匹配参数内容


