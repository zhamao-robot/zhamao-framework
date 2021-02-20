# CQ 码（多媒体消息）

消息中的多媒体内容使用 CQ 码来表示，形如 `[CQ:face,id=178]`。其中，`[CQ:]` 是固定格式；`face` 是「功能名」，除了 `face` 还有许多不同的功能名；`id=178` 是「参数」，某些功能不需要参数，而另一些需要多个参数，当有多个参数时，参数间使用逗号分隔。

## 格式

一些 CQ 码的例子如下：

```
[CQ:shake]
[CQ:face,id=178]
[CQ:share,title=标题,url=http://baidu.com]
```

更多 CQ 码功能请参考 [消息段类型](https://github.com/howmanybots/onebot/blob/master/v11/specs/message/segment.md)。

!!! warning "注意"

	CQ 码中不应有多余的空格，例如不应该使用 `[CQ:face, id=178]`。
	
	CQ 码的参数值可以包含空格、换行、除 `[],&` 之外的特殊符号等。在解析时，应直接取 `[CQ:` 后、第一个 `,` 或 `]` 前的部分为功能名，第一个 `,` 之后到 `]` 之间的部分为参数，按 `,` 分割后，每个部分第一个 `=` 前的内容为参数名，之后的部分为参数值。例如 `[CQ:share,title=标题中有=等号,url=http://baidu.com]` 中，功能名为 `share`，`title` 参数值为 `标题中有=等号`，`url` 参数值为 `http://baidu.com`。

## 转义

CQ 码中包含一些特殊字符：`[`、`]`、`,` 等，而 CQ 码又是可能混杂在纯文本内容之中的，因此消息中的纯文本内容需要对特殊字符进行转义，以避免歧义。具体的转义规则如下：

| 转义前 | 转义后  |
| ------ | ------- |
| `&`    | `&amp;` |
| `[`    | `&#91;` |
| `]`    | `&#93;` |

另一方面，CQ 码内部的参数值也可能出现特殊字符，也是需要转义的。由于 `,`（半角逗号）在 CQ 码中用于分隔参数，因此除了上面的转义规则，还需要对 `,` 进行转义，如下：

| 转义前 | 转义后  |
| ------ | ------- |
| `&`    | `&amp;` |
| `[`    | `&#91;` |
| `]`    | `&#93;` |
| `,`    | `&#44;` |

例如，一个链接分享消息的 CQ 码可能如下：

```
[CQ:share,title=震惊&#44;小伙睡觉前居然...,url=http://baidu.com/?a=1&amp;b=2]
```

## 封装调用

框架提供了 CQ 码的封装，你可以在任何位置使用封装好的 CQ 码生成器。

生成器是一个静态类，里面的方法全部是静态调用，命名空间是：`ZM\API\CQ`。

例如，给用户发送图片这样写就好啦！只需要将添加图片的地方拼到回复用户的字符串里。如果只发图片，整个字符串里只能有 CQ 码。

```php
<?php
namespace Module\Example;
use ZM\API\CQ;
use ZM\Annotation\CQ\CQCommand;
class Hello {
    /**
     * @CQCommand("发送图片")
     */
    public function msgRecv() {
        return CQ::image("https://zhamao.xin/file/hello.jpg");
        // 相当于返回："[CQ:image,file=https://zhamao.xin/file/hello.jpg]"
    }
}
```

效果

<chat-box>
) 发送图片
[ https://zhamao.xin/file/hello.jpg
</chat-box>

## CQ 码操作

### CQ::decode()

CQ 码字符反转义。

定义：`CQ::encode($msg, $is_content = false)`

当 `$is_content` 为 true 时，会将 `&#44;` 转义为 `,`。

| 反转义前 | 反转义后 |
| -------- | -------- |
| `&amp;`  | `&`      |
| `&#91;`  | `[`      |
| `&#93;`  | `]`      |
| `&#44;`  | `,`      |

```php
$str = CQ::decode("&#91;CQ:at,qq=我只是一条普通的文本&#93;");
// 转换为 "[CQ:at,qq=我只是一条普通的文本]"
```

### CQ::encode()

转义 CQ 码的敏感符号，防止 酷Q 把不该解析为 CQ 码的消息内容当作 CQ 码处理。

```php
$str = CQ::encode("[CQ:我只是一条普通的文本]");
// $str: "&#91;CQ:我只是一条普通的文本&#93;"
```

定义：`CQ::encode($msg, $is_content = false)`

当 `$is_content` 为 true 时，会将 `,` 转义为 `&#44;`。

### CQ::escape()

同 `CQ::encode()`。

### CQ::removeCQ()

去除字符串中所有的 CQ 码。

```php
$str = CQ::removeCQ("[CQ:at,qq=all]这是带表情的全体消息[CQ:face,id=8]");
// $str: "这是带表情的全体消息"
```

### CQ::getCQ()

解析 CQ 码。

- 参数：`getCQ($msg);`：要解析出 CQ 码的消息。
- 返回：`数组 | null`，见下表

| 键名   | 说明                                                         |
| ------ | ------------------------------------------------------------ |
| type   | CQ码类型，比如 `[CQ:at]` 中的 `at`                           |
| params | 参数列表，比如 `[CQ:image,file=123.jpg,url=http://a.com/a.jpg]`，params 为  `["file" => "123","url" => "http://a.com/a.jpg"]` |
| start  | 此 CQ 码在字符串中的起始位置                                 |
| end    | 此 CQ 码在字符串中的结束位置                                 |

### CQ::getAllCQ()

解析 CQ 码，和 `getCQ()` 的区别是，这个会将字符串中的所有 CQ 码都解析出来，并以同样上方解析出来的数组格式返回。

```php
CQ::getAllCQ("[CQ:at,qq=123]你好啊[CQ:at,qq=456]");
/*
[
  [
    "type" => "at",
    "params" => [
      "qq" => "123",
    ],
    "start" => 0,
    "end" => 13,
  ],
  [
    "type" => "at",
    "params" => [
      "qq" => "456",
    ],
    "start" => 17,
    "end" => 30,
  ],
]
*/
```

## CQ 码列表

### CQ::face() - 发送 QQ 表情

发送 QQ 原生表情。

定义：`CQ::face($id)`

参数：`$id` 为 QQ 表情对应的 ID 号，一些常见的表情 ID 对应的表情样式见 [QQ 对应表情ID表](/assets/face_id.html)。

```php
/**
 * @CQCommand("打盹")
 */
public function faceTest() {
    ctx()->reply("正在打盹...");
    ctx()->reply(CQ::face(8));
}
```

<chat-box>
) 打盹
( 正在打盹...
[ https://docs-v1.zhamao.xin/face/8.gif
</chat-box>

!!! note "提示"
	对于不断更新的 QQ 版本下，可能会持续扩充新的 QQ 表情，如果上表没有新的表情的话，也可以使用消息接收的方式，让机器人收到表情后解析出来对应的 id 然后再发送。


### CQ::image() - 发送图片

发送图片。

定义：`image($file, $cache = true, $flash = false, $proxy = true, $timeout = -1)`

参数

| 参数名    | 收   | 发   | 默认值  | 说明                                                         |
| --------- | ---- | ---- | ------- | ------------------------------------------------------------ |
| `file`    | ✓    | ✓    | 必填    | 图片文件名                                                   |
| `flash`   | ✓    | ✓    | `false` | 图片类型，当参数为 true 时代表发送闪照                       |
| `cache`   |      | ✓    | `true`  | 只在通过网络 URL 发送时有效，表示是否使用已缓存的文件，默认 `true` |
| `proxy`   |      | ✓    | `true`  | 只在通过网络 URL 发送时有效，表示是否通过代理下载文件（需通过环境变量或配置文件配置代理），默认 `true` |
| `timeout` |      | ✓    | `-1`    | 只在通过网络 URL 发送时有效，单位秒，表示下载网络文件的超时时间，默认 -1 不超时 |

发送时，`file` 参数除了支持使用收到的图片文件名直接发送外，还支持：

- 绝对路径，例如 `file:///root/imagetest/1.png`，格式使用 [`file` URI](https://tools.ietf.org/html/rfc8089)
- 网络 URL，例如 `http://i1.piimg.com/567571/fdd6e7b6d93f1ef0.jpg`
- Base64 编码，例如 `base64://iVBORw0KGgoAAAANSUhEUgAAABQAAAAVCAIAAADJt1n/AAAAKElEQVQ4EWPk5+RmIBcwkasRpG9UM4mhNxpgowFGMARGEwnBIEJVAAAdBgBNAZf+QAAAAABJRU5ErkJggg==`

### CQ::record() - 发送语音

发送语音消息。

定义：`CQ::record($file, $magic = false, $cache = true, $proxy = true, $timeout = -1)`

参数

| 参数名    | 收   | 发   | 默认值  | 说明                                                         |
| --------- | ---- | ---- | ------- | ------------------------------------------------------------ |
| `file`    | ✓    | ✓    | 必填    | 音频文件名                                                   |
| `flash`   | ✓    | ✓    | `false` | 图片类型，当参数为 true 时代表发送闪照                       |
| `cache`   |      | ✓    | `true`  | 只在通过网络 URL 发送时有效，表示是否使用已缓存的文件，默认 `true` |
| `proxy`   |      | ✓    | `true`  | 只在通过网络 URL 发送时有效，表示是否通过代理下载文件（需通过环境变量或配置文件配置代理），默认 `true` |
| `timeout` |      | ✓    | `-1`    | 只在通过网络 URL 发送时有效，单位秒，表示下载网络文件的超时时间，默认 -1 不超时 |

发送时，`file` 参数除了支持使用收到的语音文件名直接发送外，还支持其它形式，参考上方发送图片。

```php
/**
 * @CQCommand("说你好")
 */
public function say() {
    ctx()->reply(CQ::record("https://zhamao.xin/file/hello.mp3"));
}
```

<chat-box>
) 说你好
( [语音消息，点击收听]  2'' )))
</chat-box>

>  此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::at() - 群里@某人或全体

在群里 at 某个人或全体成员（全体成员需要有管理员权限）。

定义：`CQ::at($qq)`

参数：`$qq` 参数必填，如果填的是 QQ 号，则是单独 at 某人，如果是 `all`，则是 at 全体成员。

```php
/**
 * @CQCommand("at测试")
 */
public function atTest() {
    ctx()->reply(CQ::at(627577391)." 你好啊！");
}
```

<chat-box>
) at测试
( @鲸鱼 你好啊！
</chat-box>

### CQ::video() - 发送短视频

发送短视频。

定义：`CQ::video($file, $cache = true, $proxy = true, $timeout = -1)`

参数

| 参数名    | 收   | 发   | 默认值 | 说明                                                         |
| --------- | ---- | ---- | ------ | ------------------------------------------------------------ |
| `file`    | ✓    | ✓    | 必填   | 短视频文件名                                                 |
| `cache`   |      | ✓    | `true` | 只在通过网络 URL 发送时有效，表示是否使用已缓存的文件，默认 `true` |
| `proxy`   |      | ✓    | `true` | 只在通过网络 URL 发送时有效，表示是否通过代理下载文件（需通过环境变量或配置文件配置代理），默认 `true` |
| `timeout` |      | ✓    | `-1`   | 只在通过网络 URL 发送时有效，单位秒，表示下载网络文件的超时时间，默认 -1 不超时 |

发送时，`file` 参数除了支持使用收到的视频文件名直接发送外，还支持其它形式，参考上方发送图片。

> 此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::rps() - 猜拳

定义：`CQ::rps()`

用法：`CQ::rps()`

> 此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::dice() - 掷骰子

定义：`CQ::dice()`

用法：`CQ::dice()`

> 此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::shake() - 窗口抖动

定义：`CQ::shake()`

用法：`CQ::shake()`

> 此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::poke() - 戳一戳

发送戳一戳。

定义：`CQ::poke($type, $id, $name = "")`

参数

| 参数名 | 收   | 发   | 可能的值                                                     | 说明   |
| ------ | ---- | ---- | ------------------------------------------------------------ | ------ |
| `type` | ✓    | ✓    | 见 [Mirai 的 PokeMessage 类](https://github.com/mamoe/mirai/blob/f5eefae7ecee84d18a66afce3f89b89fe1584b78/mirai-core/src/commonMain/kotlin/net.mamoe.mirai/message/data/HummerMessage.kt#L49) | 类型   |
| `id`   | ✓    | ✓    | 同上                                                         | ID     |
| `name` | ✓    |      | 同上                                                         | 表情名 |

例子：`CQ::poke(6,-1)`

效果：放大招

> 此 CQ 码只能用于单独一条文本消息中，如果混有其他字符串，则会吞掉其他字符串内容。

### CQ::anonymous() - 匿名发消息

匿名发消息。需要在允许匿名发消息的群里发。

!!! tip "提示"

	当收到匿名消息时，需要通过 [消息事件的群消息](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/message.md#群消息) 的 `anonymous` 字段判断。

定义：`CQ::anonymous($ignore = 1)`

```php
/**
 * @CQCommand("匿名测试")
 */
public function anonymousTest() {
    ctx()->reply(CQ::anonymous()."匿名测试");
}
```

### CQ::share() - 链接分享

发送链接分享卡片，可自定义内容。

定义：`CQ::share($url, $title, $content = null, $image = null)`

参数

| 参数名    | 收   | 发   | 可能的值 | 说明           |
| --------- | ---- | ---- | -------- | -------------- |
| `url`     | ✓    | ✓    | -        | URL            |
| `title`   | ✓    | ✓    | -        | 标题           |
| `content` | ✓    | ✓    | -        | 可选，内容描述 |
| `image`   | ✓    | ✓    | -        | 可选，图片 URL |

```php
/**
 * @CQCommand("链接分享测试")
 */
public function shareTest() {
    ctx()->reply(CQ::share("https://baidu.com", "UC忽悠部", "震惊！我市一男子在光天化日之下..."));
}
```

### CQ::contact() - 推荐好友

发送推荐好友的卡片。

定义：`CQ::contact($type, $id)`

参数

| 参数名 | 收   | 发   | 可能的值      | 说明                   |
| ------ | ---- | ---- | ------------- | ---------------------- |
| `type` | ✓    | ✓    | `qq`，`group` | 推荐好友或群           |
| `id`   | ✓    | ✓    | -             | 被推荐人的 QQ 号或群号 |

```php
/**
 * @CQCommand("我的名片")
 */
public function myCard() {
    ctx()->reply(CQ::contact("qq", ctx()->getUserId()));
}
```

### CQ::location() - 发送位置

发送位置，基于经纬度坐标发的。

定义：`CQ::location($lat, $lon, $title = "", $content = "")`

参数

| 参数名    | 收   | 发   | 可能的值 | 说明           |
| --------- | ---- | ---- | -------- | -------------- |
| `lat`     | ✓    | ✓    | -        | 纬度           |
| `lon`     | ✓    | ✓    | -        | 经度           |
| `title`   | ✓    | ✓    | -        | 可选，标题     |
| `content` | ✓    | ✓    | -        | 可选，内容描述 |

### CQ::music() - 音乐分享

分享音乐，通过卡片。

发送音乐分享卡片。此 CQ 码如果伴随着其他文字，则文字内容会被丢弃。

定义：`CQ::music($type, $id_or_url, $audio = null, $title = null, $content = null, $image = null)`

- `$type`: 发送类型
- `$id_or_url`: 音乐的 id 或 音乐卡片点进去打开的链接
- `$audio`: 音频文件的 HTTP 地址
- `$title`: 音乐卡片的标题，建议 12 字以内
- `$content`: 音乐卡片的简介内容（可选）
- `$image`: 音乐卡片的图片的链接地址（可选）

如果 `$type` 参数为 `qq` 或 `163` 或 `xiami`，则必须且只和第二个参数 `$id_or_url` 配合使用。这三个为内置分享，需要先通过搜索功能获取对应平台歌曲的 id 后使用。

如果 `$type` 参数为 `custom`，则表明此音乐卡片为用户自定义，你可以根据自己的需要自定义卡片内容和音频。此时必须填写 `$id_or_url`, `$audio`, `$title` 三个参数。

```php
ctx()->reply(CQ::music("163", "730806")); //一首我喜欢的歌
// 以内置的发送类型发送音乐卡片，我这里挑了网易云音乐的一首歌。

ctx()->reply("custom", "https://baidu.com/", "https://zhamao.xin/file/hello.mp3", "我是Siri说出来的Hello", "不服来打我呀！", "https://zhamao.xin/file/hello.jpg");
// 自定义整个卡片的每个内容
```

### CQ::forward() - 合并转发

合并转发消息。

定义：`CQ::forward($id)`

参数

```
[CQ:forward,id=123456]
```

| 参数名 | 收   | 发   | 可能的值 | 说明                                                         |
| ------ | ---- | ---- | -------- | ------------------------------------------------------------ |
| `id`   | ✓    |      | 必填     | 合并转发 ID，需通过 [`get_forward_msg` API](https://github.com/howmanybots/onebot/blob/master/v11/specs/api/public.md#get_forward_msg-获取合并转发消息) 获取具体内容 |

### CQ::node() - 合并转发自定义节点

接收时，此消息段不会直接出现在消息事件的 `message` 中，需通过 [`get_forward_msg` API](https://github.com/howmanybots/onebot/blob/master/v11/specs/api/public.md#get_forward_msg-获取合并转发消息) 获取。发送时，通过获取回来的 API 节点信息进行发送。

定义：`CQ::node($user_id, $nickname, $content)`

参数

| 参数名     | 收   | 发   | 可能的值 | 说明                                                         |
| ---------- | ---- | ---- | -------- | ------------------------------------------------------------ |
| `user_id`  | ✓    | ✓    | -        | 发送者 QQ 号                                                 |
| `nickname` | ✓    | ✓    | -        | 发送者昵称                                                   |
| `content`  | ✓    | ✓    | -        | 消息内容，支持发送消息时的 `message` 数据类型，见 [API 的参数](https://github.com/howmanybots/onebot/blob/master/v11/specs/api/#参数) |

```php
/**
 * @CQCommand("node测试")
 */
public function nodeTest() {
    ctx()->reply(CQ::node(123456, "Jack", "[CQ:face,id=123]哈喽～"));
}
```

### CQ::xml() - XML 消息

发送 QQ 兼容的 XML 多媒体消息。

定义：`CQ::xml($data)`

参数：`$data` 为 xml 字符串

```php
/**
 * @CQCommand("xml测试")
 */
public function xmlTest() {
    ctx()->reply(CQ::xml("<?xml ..."));
}
```

### CQ::json() - JSON 消息

发送 QQ 兼容的 JSON 多媒体消息。

定义：`CQ::json($data, $resid = 0)`

参数同上，内含 JSON 字符串即可。

其中 `$resid` 是面向 go-cqhttp 扩展的参数，默认不填为 0，走小程序通道，填了走富文本通道发送。

!!! tip "提示"

	因为某些众所周知的原因，XML 和 JSON 的返回不提供实例，有兴趣的可以自行研究如何编写，文档不含任何相关教程。

### CQ::_custom() - 扩展自定义 CQ 码

用于兼容各类含有被支持的扩展 CQ 码，比如 go-cqhttp 的 `[CQ:gift]` 礼物类型。

定义：`CQ::_custom(string $type_name, array $params)`

| 参数名      | 说明                                                |
| ----------- | --------------------------------------------------- |
| `type_name` | CQ 码类型，如 `music`，`at`                         |
| `params`    | 发送的 CQ 码中的参数数组，例如 `["qq" => "123456"]` |

下面是一个例子：

```php
CQ::_custom("at",["qq" => "123456","qwe" => "asd"]);
// 返回：[CQ:at,qq=123456,qwe=asd]
```

