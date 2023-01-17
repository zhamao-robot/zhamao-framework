# ZMRequest HTTP 客户端

ZMRequest 是基于底层 PSR-18 封装的 HTTP 客户端，支持 HTTP/HTTPS 协议，方便用户快速实现 HTTP 请求。

## 使用

目前，ZMRequest 提供 GET 和 POST 两种请求方式，使用方式如下：

```php
use ZM\Utils\ZMRequest;

// GET 请求
$response = ZMRequest::get('https://httpbin.org/get');

// POST 请求
$response = ZMRequest::post('https://httpbin.org/post', data: [
    'foo' => 'bar',
]);
```

## 响应

ZMRequest 的响应取决于 `only_body` 参数，如果为 `true`，则只返回响应体（`string`），否则返回完整的响应（`ResponseInterface`）。

默认情况下，`only_body` 为 `true`，即只返回响应体。

```php
use ZM\Utils\ZMRequest;

// 只返回响应体
$response = ZMRequest::get('https://httpbin.org/get', only_body: true);

// 返回完整的响应
$response = ZMRequest::get('https://httpbin.org/get', only_body: false);
$content = $response->getBody()->getContents();
```

## 错误处理

在请求失败或异常时，ZMRequest 的 `get` 和 `post` 方法会返回 `false`，用户需要自行处理错误。

```php
use ZM\Utils\ZMRequest;

$response = ZMRequest::get('https://httpbin.org/get');
if ($response === false) {
    // 请求失败或异常
}
```
