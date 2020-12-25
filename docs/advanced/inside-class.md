# 内部类文件手册

这个章节写明了在框架使用过程中可能涉及到的框架内部或 Swoole、其他 composer 依赖组件的内部类，这里会根据类的命名空间一一说明。

## Swoole\Http\Request

此类是 Swoole 内部的一个类，一般在收到 HTTP 请求时，在 `@RequestMapping` 或 `@OnSwooleEvent("request")` 两个注解下可用，用作获取 GET、POST参数，上传到后端的文件、Cookies 等。详见 [Swoole 文档 - Request](http://wiki.swoole.com/#/http_server?id=httprequest) 。

### 属性

- `$fd`：获取当前连接的文件描述符 ID。
- `$header`：`HTTP` 请求的头部信息。类型为数组，所有 `key` 均为小写。
- `$server`：`HTTP` 请求相关的服务器信息。
- `$cookie`：获取 Cookies。
- `$get`：获取 GET 参数。
- `$post`：获取 POST 参数。
- `$files`：获取上传的文件信息

### 方法

- `rawContent()`：获取 POST 包原始二进制内容，相当于原生 PHP 的 ` file_get_contents("php://input");` 。
- `getData()`：获取完整的原始 `Http` 请求报文。包括 `Http Header` 和 `Http Body`

### 示例

```php
TODO：先放一放。
```

