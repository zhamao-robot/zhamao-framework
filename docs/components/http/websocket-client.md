# 框架内置 WebSocket 客户端

> 框架内置客户端在 `3.1.8` 及以后版本可用。

炸毛框架内置了 WebSocket 客户端，可以使用它发起一个 WebSocket 连接，接入其他类型的服务端。例如与另一个框架节点通信、调用第三方 WebSocket API 等。

## 创建

一般情况下，在 `#[Init]` 初始化的注解事件中创建 WebSocket 客户端，并通过一个单独的全局变量保存它：

```php
class Demo 
{
    private static ?\OneBot\Driver\Interfaces\WebSocketClientInterface $client = null;

    #[Init]
    public function onInit()
    {
        self::$client = zm_websocket_client('ws://192.168.1.3:20001/');
        self::$client->setMessageCallback(function(\Choir\WebSocket\FrameInterface $frame) {
            logger()->info('收到了服务端发来的消息：' . $frame->getData());
        });
        if (self::$client->connect()) {
            logger()->info('连接成功！');
        } elseif (!self::$client->reconnect()) {
            logger()->warning('连接失败！');
        }
        self::$client->send('hello');
    }
}
```

::: tip 提示

- 最好不要在 `#[Route]`、`#[BotEvent]` 等非可控注解中创建过多 WebSocket 客户端，避免出现资源耗尽的情况。
- `connect()` 只能调用一次，如果第一次连接失败则必须调用 `reconnect()` 进行重连。

:::

创建连接时的定义如下：

`zm_websocket_client(string $address, array $header = [], $set = null)`

其中，`$address` 为目标的地址，`$header` 为附带的请求头。`$set` 参数一般无需设置，只有在使用 Swoole 驱动且需要配置的时候传入。

## 设置回调

如果要接收服务端发来的消息，你可以通过 `setMessageCallback()` 设置回调：

```php
// 直接打印收到的消息
$client->setMessageCallback(function(\Choir\WebSocket\FrameInterface $frame) {
    dump($frame->getData());
});

// 做个复读机，收到后立刻发回原消息
$client->setMessageCallback(function(\Choir\WebSocket\FrameInterface $frame, $client) {
    dump($frame->getData());
    $client->send($frame);
});
```

如果要在服务端主动断开连接时触发事件，可以通过 `setCloseCallback()` 设置回调：

```php
$client->setCloseCallback(function(\Choir\WebSocket\CloseFrameInterface $frame) {
    logger()->info('连接断开！');
});
```

## 发送消息帧

连接成功后，你可以通过调用 `send()` 来发送消息帧。这个方法支持消息帧对象（`\Choir\WebSocket\FrameInterface`）和字符串。
在使用字符串发送时，底层会自动打包为 UTF-8 格式的消息帧发送。

```php
$result = $client->send('hello');
$result = $client->send(\Choir\WebSocket\FrameFactory::createTextFrame('hello using frame'));
```

## 检查连接状态

你可以使用 `isConnected()` 来检查连接状态：

```php
dump($client->isConnected());
```
