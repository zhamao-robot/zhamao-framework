# MessageUtil 消息处理工具类

类定义：`\ZM\Utils\MessageUtil`

> 2.3.0 版本起可用。

这里放置一些机器人聊天消息处理的便捷静态方法，例如下载图片等。

## 方法

### downloadCQImage()

下载用户消息中所带的所有图片，并返回文件路径。

定义：`downloadCQImage($msg, $path = null)`

参数 `$msg` 为带图片的用户消息，例如 `你好啊！\n[CQ:image,file=a.jpg,url=https://zhamao.xin/file/hello.jpg]`

参数 `$path` 为图片下载的路径，如果不填（默认 null）则指定为 `zm_data/images/` 目录，且不存在会自动创建。

```php
$r = MessageUtil::downloadCQImage("你好啊！\n[CQ:image,file=a.jpg,url=https://zhamao.xin/file/hello.jpg]");
/*
$r == [
    "/path-to/zhamao-framework/zm_data/images/a.jpg"
];
*/
```

如果返回的是空数组 `[ ]`，则表明消息中没有图片。如果返回的是 `false`，则表明其中至少一张下载失败或路径有误。

### containsImage()

检查消息中是否含有图片。

定义：`containsImage($msg)`

返回：`bool`，你懂的，true 就是有，false 就没有。

```php
MessageUtil::containsImage("[CQ:image,file=a.jpg,url=http://xxx]"); // true
MessageUtil::containsImage("[CQ:face,id=140] 咦，这是一条带表情的消息"); // false
```

### isAtMe()

检查消息中是否含有@bot的消息。

定义：`isAtMe($msg, $me_id)`

参数 `$me_id` 为Bot的QQ号。

返回：`bool`，true 就是有，false 就没有。

```php
MessageUtil::isAtMe("[CQ:at,qq=123456]炸毛你好","123456"); // true
MessageUtil::isAtMe("[CQ:at,qq=123456789]另一个朋友你好","123456"); // false
```

### getImageCQFromLocal()

通过文件路径获取图片的发送 CQ 码。

定义：`getImageCQFromLocal($file, $type = 0)`

参数 `$file` 为图片的绝对路径。

返回：图片的 CQ 码，如 `[CQ:image,file=xxxxx]`

参数 `$type`：

- `0`：以 base64 的方式发送图片，返回结果如 `[CQ:image,file=base64://xxxxxx]`
- `1`：以 `file://` 本地文件的方式发送图片，返回结果如 `[CQ:image,file=file:///path-to/images/a.jpg]`
- `2`：返回图片的 http:// CQ 码（默认为 /images/ 路径就是文件对应所在的目录），如 `[CQ:image,file=http://127.0.0.1:20001/images/a.jpg]`

### splitCommand()

切割用户消息为数组形式（`@CQCommand` 就是使用此方式切割的）

定义：`splitCommand($msg): array`

返回：数组，切分后的。

!!! tip "为什么不直接使用 explode 呢"

	因为 `explode()` 只会简单粗暴的切割字符串，假设用户输入的消息中两个词中间有多个空格，则会有空的词出现。例如 `你好     我是一个长空格`。此函数会将多个空格当作一个空格来对待。

```php
MessageUtil::splitCommand("你好 我是傻瓜\n我是傻瓜二号"); // ["你好","我是傻瓜","我是傻瓜二号"]
MessageUtil::splitCommand("我有   三个空格"); // ["我有","三个空格"]
```

### matchCommand()

匹配一条消息到 `@CQCommand` 规则的注解事件，返回要执行的类和函数位置。

定义：`matchCommand($msg, $obj)`

参数 `$msg` 为消息内容。

参数 `$obj` 为事件的对象，可使用 `ctx()->getData()` 获取原先的事件体（仅限 OneBot 消息类型事件中使用）

返回：`\ZM\Entity\MatchObject` 对象，含有匹配成功与否，匹配到的注解对象，匹配到的分割词等，见 []

### addShortCommand()

快速添加一条静态消息回复命令。

定义：`addShortCommand($command, string $reply)`

参数 `$command` 为问的内容，如 `炸毛不聪明`。

参数 `$reply` 为回复的内容，如 `其实还是很聪明的！`。

这个命令推荐在 `@OnStart` 注解下使用，可以用这个来做一个动态的词库，从文件加载后使用。

```php
/**
 * @OnStart()
 */
public function onStart() {
    MessageUtil::addShortCommand("炸毛不聪明", "其实还是很聪明的！");
}
```

<chat-box>
) 炸毛不聪明
( 其实还是很聪明的！
</chat-box>

### strToArray()

将 `string` 类型的消息文本转换为 `array` 格式。

定义：`strToArray($msg, bool $ignore_space = true, bool $trim_text = false)`

参数 `$msg` 为带 OB/CQ 码的字符串消息，如 `你好啊，[CQ:at,qq=123]`。

参数 `$ignore_space` 在 `false` 时，转换的数组内会包含空 `text` 段。

参数 `$trim_text` 为 `true` 时，会自动去除 `text` 段消息头尾的换行符和空格。

这个命令转换的数组格式符合 OneBot 11/12 标准，但细节上可能会与不同 OneBot 实现有所差异。

```php
$str = "你好啊，[CQ:at,qq=123]";
$arr = \ZM\Utils\MessageUtil::strToArray($str);
```

转换结果参考如下：

```json
[
    {
        "type": "text",
        "data": {
            "text": "你好啊，"
        }
    },
    {
        "type": "at",
        "data": {
            "qq": "123"
        }
    }
]
```

### arrayToStr()

将 `array` 格式的消息内容转换为字符串 + CQ 码的形式。

定义：`arrayToStr(array $array)`

```php
// 我们使用上边的 $arr 作为传入值。
$new_str = \ZM\Utils\MessageUtil::arrayToStr($arr);
// 结果："你好啊，[CQ:at,qq=123]"
```