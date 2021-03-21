# 内部类文件手册

这个章节写明了在框架使用过程中可能涉及到的框架内部或 Swoole、其他 composer 依赖组件的内部类，这里会根据类的命名空间一一说明。

## Swoole\Http\Request

此类是 Swoole 内部的一个类，一般在收到 HTTP 请求时，在 `@RequestMapping` 或 `@OnRequestEvent()` 两个注解下可用，用作获取 GET、POST参数，上传到后端的文件、Cookies 等。详见 [Swoole 文档 - Request](http://wiki.swoole.com/#/http_server?id=httprequest) 。

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

## ZM\Entity\MatchObject

此类是调用方法 `MessageUtil::matchCommand()` 返回的对象体，含有匹配成功与否和匹配到的注解相关的信息。

### 属性

- `$match`：`bool` 类型，返回匹配是否成功
- `$object`：`CQCommand` 注解类，如果匹配成功则返回对应的 `@CQCommand` 信息
- `match`：`array` 类型，如果匹配成功则返回匹配到的参数

```php
// 假设我有一个注解事件 @CQCommand(match="你好")，绑定的函数是 \Module\Example\Hello 下的 hello123()

$obj = MessageUtil::matchCommand("你好 我叫顺溜 我今年二十八", ctx()->getData());
/* 以下是返回信息，仅供参考
$obj->match ==> true
$obj->object ==> \ZM\Annotation\CQ\CQCommand: (
	match: "你好",
    pattern: "",
    regex: "",
    start_with: "",
    end_with: "",
    keyword: "",
    alias: [],
    message_type: "",
    user_id: 0,
    group_id: 0,
    discuss_id: 0,
    level: 20,
    method: "hello123",
    class: \Module\Example\Hello::class
)
$obj->match ==> [
	"我叫顺溜",
	"我今年二十八"
]
*/
```



