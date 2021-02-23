# 接入 WebSocket 客户端

炸毛框架其实从本质上讲，就是一个 HTTP + WebSocket 服务器，所以框架也支持对接其他任何 HTTP 客户端和 WebSocket 客户端，实际上炸毛框架非常适合用 WebSocket 做在线的 IM 聊天通讯，也可以方便地进行 WS 通信。这里主要说明如何对接一个自定义的 WebSocket 客户端。

## 类型指定

由于 WebSocket 连接都具有同样的性质，没有状态，所以在建立 WebSocket 连接的时候，需要客户端表明自己的身份和类型。指定客户端连接类型的方式有两种：

- `GET` 参数传递，在连接的时候，加上 GET 参数 `type` 即可。比如 js 中 WebSocket 建立时地址写：`ws://127.0.0.1:20001/?type=foo`，这时传入的连接就是 `foo` 类型。
- `Header` 传递，用户需要在建立连接时指定 HTTP 的头部信息 `X-Client-Role`，例如 `X-Client-Role: foo`，这时传入的连接就是 `foo` 类型。

以上两种方式，`Header` 方式比 `GET` 方式优先级要高，如果两者均没有指定，框架会将此连接当作 `default` 类型接入。

!!! note "提示"

	对于对接 OneBot 标准的机器人客户端，只要符合 OneBot 标准，即 `X-Client-Role` 会自动带上 `universal`、`qq` 等字样，就会自动标记为 `qq` 类型。

## 逻辑编写

传入连接后，我们就能通过注解事件绑定来做我们自己想做的事情了！比如下方是传入类型为 foo 连接要做的事情

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\Console\Console;
use ZM\ConnectionManager\ConnectionObject;
class Hello {
	/**
 	 * @OnOpenEvent("foo")
 	 */
	public function onFooConnect(ConnectionObject $conn) {
	    Console::info($conn->getName()." 已连接！");
	}
```

以上作用就是在终端输出 `foo 已连接！` 这个提示的。关于 `ConnectionObject` 对象，见下方。

## WS 连接对象

对于每一个 WebSocket 连接，框架内都有一个专属的操作类，有获取类型名称、保存链接参数和属性以及获取文件标识符等功能。

### getFd()

获取文件标示符，用于发送消息、接收消息等。这个参数获取的 `fd` 是 Swoole 指定的，用于发送信息等。

```php
$fd = $conn->getFd();
server()->send($fd, "hello world");
```

> WebSocket 是全双工的，所以发送和接收其实是互不干扰的，你可以不仅仅在 WebSocket 相关的上下文中，还可以比如在 HTTP 或者机器人上下文中给别的 WebSocket 客户端发请求。

### getName()

获取连接对象绑定的连接类型，例如上方提到的 `foo`、`default` 等。

```php
Console::info("当前连接类型：".$conn->getName()); //当前连接类型：foo
```

### setName()

改变连接对象绑定的连接类型，例如从 `foo` 改为 `bar`。

```php
$s = $conn->getName(); // foo
$conn->setName("bar");
$s = $conn->getName(); // bar
```

### getOptions()

获取此连接存储的所有参数，以数组形式。存储内容见下方 `setOption()`。

格式：`["参数1" => {参数1的值}, "参数2" => {参数2的值}]`

### getOption()

获取此连接存储的参数，获取指定名称的，此方法拥有一个参数 `$key`，指定即可获取。

如果没有对应参数，则返回 `null`。

我们在前面的机器人部分知道，框架主要是用于机器人的连接，那么机器人客户端在连接后，比如我们想知道这个机器人的 WS 连接对应的是哪个 QQ 号的机器人，我们就可以用 `getOption("connect_id")` 来获取。这个 `connect_id` 是 OneBot 标准的客户端接入后自动填入的一个参数。例如，我们想在机器人接入后打出接入机器人的 QQ 号：

```php
/**
 * @OnOpenEvent("qq")
 */
public function onQQConnect($conn) {
    Console::success("机器人 ".$conn->getOption("connect_id")." 已连接！"); // 机器人 123456 已连接！
}
```

### setOption()

设置连接存储的参数。参数：`setOption($key, $value)`。`$key` 限定为 `connect_id` 一种。（因为目前有了 LightCache，所以这里暂时不提供别的 key 设定）

```php
$conn->setOption("connect_id", "asdasdasd"); // $value 最长长度为 29
```

## 发送到 WebSocket 客户端

很简单，从上面获取到 `fd` 后使用下面的方式就可以了～

```php
server()->push($conn->getFd(), "hello"); // 第二个为 string 类型的参数
```

## 从客户端接收

接收消息必须从 `@OnMessageEvent` 注解事件下接收，使用上下文 `ctx()->getFrame()` 获取消息帧。

从这里获取的 `Frame` 对象，见 [Swoole 文档 - Frame](https://wiki.swoole.com/#/websocket_server?id=swoolewebsocketframe)。

Frame 对象有四个参数：

- `$frame->fd`：获取发来帧的 fd
- `$frame->data`：数据本体
- `$frame->opcode`：数据类型 int 值，见 [Swoole 文档 - 数据帧类型](https://wiki.swoole.com/#/websocket_server?id=%e6%95%b0%e6%8d%ae%e5%b8%a7%e7%b1%bb%e5%9e%8b)
- `$frame->finish`：是否发送完毕，bool

下面以接收一个 json 字符串为例，并进行后续的解析：

```php
/**
 * @OnMessageEvent("foo")
 */
public function onMessage() {
    $frame = ctx()->getFrame();
    $json_str = $frame->data; // 假设传入的是 {"key1":"value1","k2":"v2"}
    $json = json_decode($json_str, true);
    Console::info("key1 的值是:" . $json["key1"]);
}
```

## 关闭连接

```php
server()->close($conn->getFd());
```

