# ZMRequest（HTTP 客户端）

框架提供了轻量的 HTTP 请求发起工具类，直接静态调用即可。

命名空间：`use ZM\Requests\ZMRequest;`

::: warning 注意

在使用 Swoole 4.6.0 以下（不包含）的版本时，最好使用 Swoole 官方推荐的 Saber 或者 ZMRequest 这个轻量的 HTTP 请求客户端，不要使用 curl_exec，因为在老版本的 Swoole 上对 curl 的协程 Hook 支持不是很完善。

:::

## ZMRequest::get()

发起 GET 请求。

定义：`ZMRequest::get($url, $headers = [], $set = [], $return_body = true)`

全局函数别名：`zm_request_get($url, $headers = [], $set = [], $return_body = true)`

`$url`：要请求的 url，如 `http://captive.apple.com/`

`$headers`：要请求的 Headers，例如：`["User-Agent" => "Chrome"]`，数组形式

`$set`：请求时的一些设置，例如超时时间等等。详见下方“设置参数”

`$return_body`：是否只返回请求回来的内容部分，默认为 true，如果为 false 时则会返回一个 `\Swoole\Coroutine\Http\Client` 对象，可查阅 [Swoole 文档](http://wiki.swoole.com/#/coroutine_client/http_client) 进行接下来的一系列操作。

如果 `$return_body` 为 true，但是请求失败（HTTP 状态码不是 200 或无法连接到目标服务器或者无法解析域名等问题）时，方法会返回 false，否则会返回内容。

返回值：`false|string|\Swoole\Coroutine\Http\Client`

```php
$r = ZMRequest::get("http://captive.apple.com/", ["User-Agent" => "Chrome"]);
echo $r.PHP_EOL; // <HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>
```

```php
$r = zm_request_get("http://captive.apple.com/", [], [], false);
echo $r->body.PHP_EOL; // 这行输出和上方的一致
dump($r);
/*
^ Swoole\Coroutine\Http\Client {#170
  +errCode: 0
  +errMsg: ""
  +connected: false
  +host: "captive.apple.com"
  +port: 80
  +ssl: false
  +setting: array:1 [
    "timeout" => 15.0
  ]
  +requestMethod: "GET"
  +requestHeaders: []
  +requestBody: null
  +uploadFiles: null
  +downloadFile: null
  +downloadOffset: 0
  +statusCode: 200
  +headers: array:4 [
    "content-type" => "text/html"
    "content-length" => "68"
    "date" => "Thu, 07 Jan 2021 06:22:32 GMT"
    "connection" => "keep-alive"
  ]
  +set_cookie_headers: null
  +cookies: null
  +body: "<HTML><HEAD><TITLE>Success</TITLE></HEAD><BODY>Success</BODY></HTML>"
}
*/
```

## ZMRequest::post()

发送一个 POST 请求。

定义：`ZMRequest::post($url, array $header, $data, $set = [], $return_body = true)`

全局函数别名：`zm_request_post($url, array $header, $data, $set = [], $return_body = true)`

`$url`：同上，填入 url，必填

`$header`：请求的 Headers，必填，数组形式，例如 `["Content-Type" => "application/json"]`

`$data`：请求的数据体，默认应该传入数组，如果传入 `array` 类型，则会默认当作 `Content-Type: application/x-www-form-urlencoded` 方式自动转码和转换，例如 `["key1" => "b1", "key2" => "b2"]` 会变成 `key1=b1&key2=b2`

`$set`：同上，见下面的设置参数部分。

`$return_body`：同上。

```php
$s = ZMRequest::post("http://captive.apple.com/", ["Content-Type" => "application/json"], json_encode(["key1" => "value1"]));
```

## ZMRequest::request()

发起自定义一切参数的 HTTP 请求。

参数：

- `$url`：请求的链接，自动解析端口、HTTPS、DNS 等操作 
- `$attribute`：请求的属性，示例见下方
- `$return_body`：可选参数，`bool` 类型，和上面的 `$return_body` 参数意义相同

其中 `$attribute` 参数对应可设置的有：

- `method`：可选 `GET`，`POST` 等 HTTP 请求的方式
- `set`：设置 Swoole 客户端的参数
- `headers`：要请求的 HTTP Headers
- `data`：请求的 body 数据，为数组时自动转换头部为 `x-www-form-urlencoded`
- `file`：要发送的文件，数组，可选多个文件

例1：使用 GET 请求发送带有 Body 的 HTTP 请求

```php
$r = ZMRequest::request("http://example.com", [
  "method" => "GET",
  "data" => [
    "key1" => "value1"
  ]
]);
```

例2：设置请求超时时间并指定自定义头部

```php
$r = ZMRequest::request("http://example.com", [
  "method" => "POST",
  "headers" => [
    "X-Custom-Header" => "Hello world",
    "User-Agent" => "HEICORE"
  ],
  "set" => ["timeout" => 10.0]
]);
```

例3：发送文件和 data

```php
$r = ZMRequest::request("http://example.com/sendfile", [
  "file" => [
    [
      "path" => "/path/to/image1.jpg", // path字段必填
      "name" => "file1", // name字段必填，这个是 POST 过去的 key
      //"mime_type" => "image/jpeg", // 可选字段，底层会自动推断
      //"filename" => "a.jpg", // 可选字段，文件名称
      //"offset" => 0, // 可选字段，可以从指定文件的中间部分开始传输数据，此特性用于断点续传
      //"length" => 1024 // 可选字段，默认为整个文件的尺寸
    ],
    [
      "path" => "/path/to/image2.jpg",
      "name" => "file2"
    ]
  ],
  "data" => [
    "key1" => "value1"
  ]
]);
```

## ZMRequest::downloadFile()

下载文件到本地。

定义：`ZMRequest::downloadFile($url, $dst = null)`

`$url`：不多讲，下载链接。

`$dst`：本地位置，例如 `/tmp/hello.html`

下载成功返回 true 或指定的文件位置，失败返回 false。

```php
ZMRequest::downloadFile("http://captive.apple.com/", "/tmp/apple.html");
```

## ZMRequest::websocket()

创建一个 WebSocket 连接。因为 Swoole 提供的是同步协程的方案，但对于 WebSocket 这样的全双工通信，反而不是一个好的代码逻辑，炸毛框架将此同步协程的方案封装成了异步事件调用的方式。

定义：`ZMRequest::websocket($url, $set = ['websocket_mask' => true], $header = [])`

返回：一个 `\ZM\Requests\ZMWebSocket` 对象

效果等同于：`$s = new \ZM\Requests\ZMWebSocket($url, $set = ['websocket_mask' => true], $header = [])`

这个是 ZMRequest 扩展而来的异步 WebSocket 客户端，可供方便地连接、收发 WebSocket 消息所定制。

命名空间：`\ZM\Requests\ZMWebSocket` 

```php
$ws = ZMRequest::websocket("ws://127.0.0.1:12345/"); //使用工具函数
// $ws = new ZMWebSocket("ws://127.0.0.1:12345/"); //直接构造
if($ws->is_available) {
  $ws->onMessage(function(\Swoole\WebSocket\Frame $frame, $client) {
    var_dump($frame->data);
  });
  $ws->onClose(function($client){
    Console::info("Websocket closed.");
  });
  $result = $ws->upgrade();
  var_dump($result);
}
```

### 属性

#### is_available

`bool` 类型，用于判断构造对象是否成功或链接是否可用。在构建新的对象并执行 `upgrade()` 前，如果 ws 链接没有问题，则会变为 true；在 `onClose()` 回调执行后，此值变回 false。

### 方法

#### __construct()

客户端对象的构造方法。

参数：

- `$url`：要请求到的 WebSocket 目标地址，必须以 `ws(s)://` 开头
- `$set`：可选，Swoole 客户端的参数，例如超时、是否使用 `websocket_mask` 等，如果为空数组则默认为 `["websocket_mask" => true]`，具体可设置的内容见 [Swoole 文档](https://wiki.swoole.com/#/coroutine_client/http_client?id=set)
- `$header`：可选，请求的头部信息，数组

```php
$a = new ZMWebSocket("ws://127.0.0.1:8080/", ["websocket_mask" => true], [
  "User-Agent" => "Firefox"
]);
```

#### onMessage()

设置收到消息的回调函数。

回调的参数：

- `$frame`：`Swoole\WebSocket\Frame` 类型，消息帧，一般只用 `$frame->data` 获取数据，具体见 [Swoole 文档](https://wiki.swoole.com/#/websocket_server?id=swoolewebsocketframe)
- `$client`：`Swoole\Coroutine\Http\Client` 类型，为客户端本身的对象，用于 push 数据等

```php
$a->onMessage(function($frame, $client){
  echo "收到消息：".$frame->data.PHP_EOL;
  $client->push("hello world");
});
```

#### onClose()

设置连接断开后执行的回调函数。

回调的参数：

- `$client`：同上，但断开连接后不能使用 `push()` 发送数据了，只建议作为重连等机制的使用

```php
$a->onClose(function($client){
  echo "WS 链接断开了！".PHP_EOL;
});
```

#### upgrade()

发起连接。

返回值：`true|false`，当为 `true` 时代表握手成功，此时可以在回调里愉快地收发消息了。如果为 `false` 表明握手失败。

::: warning 注意

这里由于是协程转异步，所以不能确定 `upgrade()` 和 `onMessage()` 哪个先会被触发（一般情况下如果服务器不是立刻响应回包信息，总是会先返回 `upgrade()` 的结果。

:::

## 设置参数

见：[Swoole - HTTP 客户端](http://wiki.swoole.com/#/coroutine_client/http_client?id=set)
