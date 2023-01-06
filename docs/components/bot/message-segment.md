# 机器人消息段格式

消息段是 OneBot 12 标准中约定的一部分内容，如果你想对 OneBot 12 标准的消息段部分深入了解，请到 [OneBot - 消息段](https://12.onebot.dev/interface/message/segments/)。

因为聊天信息不仅仅含有文字，还会有图片、语音、文件、at、表情包等富文本，所以消息段就是用来描述这些富文本的。

## 格式样例

消息段不同于字符串，消息段默认是一个数组，数组中的每个元素都是一个消息段对象，每个消息段对象都有一个 `type` 字段，用来描述这个消息段的类型。
下面是一个以 JSON 格式表达的消息段样例：

```json
[
  {
    "type": "text",
    "data": {
      "text": "Hello, World!"
    }
  },
  {
    "type": "image",
    "data": {
      "file_id": "blablablablabla"
    }
  }
]
```

这个消息段在实际的聊天机器人对应的窗口中可能表示为：“Hello, World!\[假设这里是一张图片\]”。

## 使用消息段

一般情况下，框架提供的消息发送和接收接口均会自动识别和转换字符串到消息段，所以你不需要手动构造消息段。
但如果你想发送富文本时，需要通过 `\MessageSegment` 对象进行构造。此对象等同于上方消息段中的单个消息段元素，在传入后发送时会自动转换为数组形式的消息段。

在框架任意位置，你可以使用全局函数 `segment()` 生成一个消息段对象。

- 定义：`segment(string $type, array $daa)`
- 返回：`OneBot\V12\Object\MessageSegment`

```php
#[BotCommand(match: '来个at')]
public function at()
{
    bot()->reply([segment('at', ['user_id' => bot()->getEvent()->getUserId()]), segment('text', ['text' => ' 这是一条at你的消息'])]);
}

#[BotCommand(match: '来个图片')]
public function image()
{
    // 我们假设你发送的图片已经上传
    bot()->reply([segment('image', ['file_id' => 'blablablablabla'])]);
}
```

<chat-box :my-chats="[
{type:0,content:'来个at'},
{type:1,content:'@123456 这是一条at你的消息'},
{type:0,content:'来个图片'},
{type:3,content:'https://zhamao.xin/file/hello.jpg'},
]" />
