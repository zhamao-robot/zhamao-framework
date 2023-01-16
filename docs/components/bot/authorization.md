# 机器人接入鉴权

允许所有客户端接入框架是不安全的，因此框架提供了一个接入鉴权的功能，只有经过鉴权的客户端才能接入框架。

目前框架根据 [OneBot 12 标准中规定的鉴权方式](https://12.onebot.dev/connect/communication/http/#_1)
进行鉴权，即通过 `access_token` 进行鉴权。

## 鉴权流程

      1. 客户端在连接时，通过 OneBot 12 标准中规定的方式传递 `access_token` 参数。
      2. 框架在接收到客户端的连接请求后，会根据 `access_token` 参数进行鉴权。
      3. 如果鉴权成功，框架将会接受客户端的连接请求，并开始分发后续事件。
      4. 如果鉴权失败，框架将会断开客户端的连接。

## 鉴权方式

框架提供了两种鉴权方式，分别是静态鉴权和动态鉴权，适应不同的使用场景。

### 静态鉴权

静态鉴权是指在配置文件中指定一个 `access_token`，并在客户端连接时传递相同的 `access_token` 参数。

```php
/* 要启动的服务器监听端口及协议 */
$config['servers'] = [
    [
        'host' => '0.0.0.0',
        'port' => 20001,
        'type' => 'websocket',
        'access_token' => '1234567890', // 静态鉴权
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20002,
        'type' => 'http',
        'flag' => 20002,
        'access_token' => '0987654321', // 静态鉴权
    ],
];
```

为不同的通信方式指定不同的 `access_token` 是完全可行的。

### 动态鉴权

在部分场景下，静态鉴权可能不太适用，例如多个客户端共享一个框架，或是客户端的 `access_token` 无法预先知晓的情况下。

框架提供了动态鉴权的功能，可以在客户端连接时，由给定的回调函数来决定是否接受客户端的连接请求。

```php
/* 要启动的服务器监听端口及协议 */
$config['servers'] = [
    [
        'host' => '0.0.0.0',
        'port' => 20001,
        'type' => 'websocket',
        'access_token' => function ($token) {
            // 动态鉴权
            return $token === '1234567890';
        },
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20002,
        'type' => 'http',
        'flag' => 20002,
        'access_token' => function ($token) {
            // 动态鉴权
            return $token === '0987654321';
        },
    ],
];
```

你可以在回调函数做任意处理，例如使用特定算法进行鉴权，或是从数据库中读取 `access_token` 进行鉴权。

## 自定义鉴权

除了上述方式外，你也可以通过留空 `access_token` 参数，并在 `WebsocketOpenEvent` 事件中自行进行鉴权。

```php
#[\BindEvent(\WebSocketOpenEvent::class)]
public function handle(WebsocketOpenEvent $event)
{
    // 自定义鉴权
    // $event->getRequest() 可以获取到客户端的连接请求
}
```

如果拒绝接入，只需返回 401 响应即可，例如：

```php
$event->withResponse(HttpFactory::createResponse(401, 'Unauthorized'));
```
