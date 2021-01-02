# OneBot 实例

## 什么是 OneBot

OneBot 是一个聊天机器人应用接口标准，详情戳[这里](https://github.com/howmanybots/onebot)。

## OneBot 实现选择

如果你使用炸毛框架作为聊天机器人的开发框架，请先选择一种兼容 OneBot 标准的机器人接口。理论上，基于 OneBot 标准开发的**任何** SDK、框架和机器人应用，都可以无缝地在下面的不同实现中切换。当然，在一小部分细节上各实现可能有一些不同。

| 项目地址                                                     | 平台                                          | 核心作者       | 备注                                                         |
| ------------------------------------------------------------ | --------------------------------------------- | -------------- | ------------------------------------------------------------ |
| [richardchien/coolq-http-api](https://github.com/richardchien/coolq-http-api) | CKYU                                          | richardchien   | 可在 Mirai 平台使用 [mirai-native](https://github.com/iTXTech/mirai-native) 加载 |
| [Mrs4s/go-cqhttp](https://github.com/Mrs4s/go-cqhttp)        | [MiraiGo](https://github.com/Mrs4s/MiraiGo)   | Mrs4s          | 炸毛框架推荐使用此项目机器人应用                             |
| [yyuueexxiinngg/cqhttp-mirai](https://github.com/yyuueexxiinngg/cqhttp-mirai) | [Mirai](https://github.com/mamoe/mirai)       | yyuueexxiinngg |                                                              |
| [takayama-lily/onebot](https://github.com/takayama-lily/onebot) | [OICQ](https://github.com/takayama-lily/oicq) | takayama       |                                                              |
| [ProtobufBot](https://github.com/ProtobufBot)                | [Mirai](https://github.com/mamoe/mirai)       | lz1998         | 事件和 API 数据内容和 OneBot 一致，通信方式不兼容            |

!!! warning "注意"

    因为目前炸毛框架 2.0 只支持 WebSocket 方式的 OneBot 实现，所以目前上述项目的连接方式均只可选支持反向 WebSocket 通信的。后期会兼容 HTTP 和正向 WebSocket 通信方式。

如果你还没有自己的 QQ，或者是其他原因导致的暂时无法使用上述 OneBot 实例，可以使用炸毛项目中的 OneBot 协议聊天模拟器。但目前还处在开发中，暂不可用。