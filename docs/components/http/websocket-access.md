# 接入其他 WebSocket 客户端

众所周知，炸毛框架提供了 HTTP、WebSocket 服务器功能，但默认的框架只接收 HTTP 请求和 OneBot 12 标准 的 WebSocket 客户端。

想要接入其他 WebSocket 客户端，例如通过浏览器前端、游戏客户端、手机移动端等方式通过 WebSocket 接入框架从而和机器人实现联动，也是很容易的。

## 接入

框架默认只会接入 `Sec-WebSocket-Protocol` 为 `12.xxx` 类型的 WS 客户端，同时默认也会断掉所有未设置连接状态信息的 WebSocket 客户端握手请求。

所以接入框架只需要写一个 `WebSocketOpenEvent` 的监听事件，在函数内通过 `ConnectionUtil::setConnection()` 方法标记该连接有效，框架就不会断掉连接，并保存该连接的信息。

```php
#[BindEvent(WebSocketOpenEvent::class)]
public function onCustomOpen(WebSocketOpenEvent $event)
{
    // 例如通过判断 Sec-WebSocket-Protocol 头来识别一个第三方客户端类型
    if ($event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol') === 'my-custom-ws-client') {
        \ZM\Utils\ConnectionUtil::setConnection($event->getFd(), ['my-custom-ws-client' => '123']);
        // 如果你的客户端要求握手回包中必须返回一个合法的 Sec-WebSocket-Protocol 头，则可以使用下面这行代码来添加额外的 Response Header
        $event->withResponse(zm_http_response(status_code: 101, headers: ['Sec-WebSocket-Protocol' => $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol')]));
    }
}
```

上方的例子的 `setConnection` 第二个参数是一个数组，你可以设置一些自己的键名和键值传入，用于保存该连接对应的信息，此后可以通过 `ConnectionUtil::getConnection($fd)` 方式获取。



## 接收和发送

接入后，你可以通过 `WebSocketMessageEvent` 来监听客户端发来的消息帧。当然，这里你需要用到一个内置的中间件，用于限定事件只获取指定类型的事件：

```php
#[BindEvent(WebSocketMessageEvent::class)]
#[Middleware(WebSocketFilter::class, ['my-custom-ws-client' => true])]
public function onCustomMessage(WebSocketMessageEvent $event, FrameInterface $frame)
{
    logger()->info('收到了自定义 ws 客户端发来的消息事件：' . $frame->getData());
}
```

这里要注意，这个中间件 `WebSocketFilter` 的后面数组内为限定查询的参数。这个限定列表支持多个参数，键名为你在 `WebSocketOpenEvent` 中通过 `setConnection()` 方法添加的连接信息。

这里的例子和上方形成了联动，比如这里使用 `['my-custom-ws-client' => true]` 的含义就是：
只要收到的 WebSocket 消息事件所属连接信息存在 `my-custom-ws-client` 字段，即为真，否则为假（不响应此事件）。

> 如果你不使用 `WebSocketFilter` 中间件做过滤，那么该 BindEvent 绑定的函数将响应所有类型的客户端连接的所有消息帧。

收到消息后，我们也可以使用 `$event->send()` 方法发送消息帧到客户端：

```php
$event->send('hello world'); // 传入字符串时，将自动转换为发送 UTF-8 文本的消息帧
$event->send(\Choir\WebSocket\FrameFactory::createTextFrame('ohuo')); // 你也可以直接传入一个符合 FrameInterface 接口的消息帧
```

## 连接断开事件

如果客户端主动断开与服务端的连接，服务端会触发 WebSocketCloseEvent 事件，同时伴随一个关闭帧。

```php
#[BindEvent(WebSocketCloseEvent::class)]
public function onClose(WebSocketCloseEvent $event)
{
    logger()->info('fd ' . $event->getFd() . ' 关闭了连接');
}
```

## 消息帧

WebSocket 通信少不了一个概念：消息帧。框架采用了 Choir 的 HTTP 组件，内包含一个 FrameInterface，消息帧的接口类型。
框架支持发送所有实现了 FrameInterface 的消息帧，例如发送 PING 包、PONG 包、二进制数据包、UTF-8 文本包。

框架默认使用的消息帧对象是：`\Choir\WebSocket\Frame`，你可以使用 FrameFactory 工厂类创建一个 Frame：

```php
$frame = \Choir\WebSocket\FrameFactory::createTextFrame('hello');   // 创建文本帧
$frame = \Choir\WebSocket\FrameFactory::createBinaryFrame(file_get_contents('a.jpg'));  // 创建二进制数据帧
$frame = \Choir\WebSocket\FrameFactory::createPingFrame();  // 创建 ping 帧
$frame = \Choir\WebSocket\FrameFactory::createPongFrame();  // 创建 pong 帧
$frame = \Choir\WebSocket\FrameFactory::createCloseFrame(1000); // 创建关闭请求帧，用于主动正常断开 WebSocket 连接，参数为 WebSocket 的状态码，可参考 RFC
```

## 事件外主动发送消息帧

在使用 WebSocket 接入客户端时，往往会有不在事件内需要向已连接的客户端发送 WebSocket 消息的情况，框架的 Driver 层抽象了全局方法 `ws_socket()` 来提供一个可在事件外发送 WS 消息的功能。

举例一，我们想在收到一个 HTTP 请求时，发送一条消息到所有已连接的同一类型 WebSocket 客户端。我们在接入客户端的时候，对客户端的 fd 做了缓存：

```php
#[BindEvent(WebSocketOpenEvent::class)]
public function onWSOpen(WebSocketOpenEvent $event)
{
    if ($event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol') === 'special-app') {
        logger()->info('special-app 已接入，正在鉴权');
        // 为了更贴近真实开发案例，这里假装通过 GET 请求传入的 token 参数进行一个鉴权，实际业务的鉴权逻辑请自行编写！
        if (($event->getRequest()->getQueryParams()['token'] ?? null) !== 'emhhbWFvLWZyYW1ld29yaw==') {
            logger()->warning('客户端 [' . $event->getFd() . '] 鉴权失败');
            return;
        }
        logger()->info('鉴权成功！');
        \ZM\Utils\ConnectionUtil::setConnection($event->getFd(), ['special-app' => time()]);
    }
}

#[Route('/ws-test/{name}')]
public function onRouteTest(array $params)
{
    // 这行的 ws_socket 传入的参数 1 为多 server 对应的 flag 参数，留空则默认使用 1。（框架的默认配置第一个 websocket 的 flag 也是 1）
    // sendMultiple 的作用在于同时到多个客户端，第二个参数是一个回调函数，可以用它来过滤选择自己相应连接
    ws_socket(1)->sendMultiple('收到了网页请求，它说它是 ' . $params['name'], fn ($fd) => isset(ConnectionUtil::getConnection($fd)['special-app']));
    // 创建一个 HTTP Response 返回用户网页
    return zm_http_response(body: 'hello, ' . $params['name']);
}
```

除此之外，你也可以使用 `ws_socket()->send()` 方法，只给一个客户端发送消息帧，通过指定第二个参数 fd 来实现：

```php
ws_socket(1)->send('hello', $fd);
```

另外，对于开发上的考虑，对于在事件外挑选一个客户端发送消息时，涉及 fd 存储的问题，你可以配合 `kv()`、`db()` 等组件对 fd 进行缓存。

## 多服务器端口

框架 3.0 支持了同时监听多个端口，例如框架默认的配置就同时监听了 20001、20002 分别做 WebSocket 服务端和 HTTP 服务端。你也可以继续让框架添加多个端口进行监听。

```php
// global.php
/* 要启动的服务器监听端口及协议 */
$config['servers'] = [
    [
        'host' => '0.0.0.0',
        'port' => 20001,
        'type' => 'websocket',
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20002,
        'type' => 'http',
        'flag' => 20002,
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20003,
        'type' => 'websocket',
        'flag' => 4,
    ],
];
```

每个服务端支持四个参数，`host`、`port`、`type`、`flag`。其中 flag 参数可忽略，忽略则默认值为 1。

flag 的用处就是在事件内区分来源服务监听的端口，例如上方的配置，我监听了两个 websocket 的端口，第一个 flag 是 1，第二个 flag 是 4。

我们在 WebSocket 的三种事件（WebSocketOpenEvent、WebSocketMessageEvent、WebSocketCloseEvent）中，均可使用 WebSocketFilter 中间件进行过滤限定 flag。
下方为一个 Open 事件使用 Filter 过滤端口 20003 来源的客户端连接：

```php
#[BindEvent(WebSocketOpenEvent::class)]
#[Middleware(WebSocketFilter::class, ['flag' => 4])]
```
