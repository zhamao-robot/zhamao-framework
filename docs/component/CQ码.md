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