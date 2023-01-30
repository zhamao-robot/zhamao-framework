# 快速上手 - 聊天机器人

在这里，我们将会以一个基础的复读机为例，帮助你快速熟悉框架的开发流程。

在开始之前，你需要先准备一个可用的 OneBot 机器人实现端（客户端）。

其中一些可用的选项为：

- Walle-Q（QQ 端）
- 更多…

## 机器人实现端

> 机器人实现端与炸毛框架相互独立。关于实现端本身的问题请向对应的开发者反馈。如果你确定相关问题由炸毛框架引起（例如缺少适配或代码问题等）请向我们报告。

框架支持多种通信方式，这里将以反向 WebSocket 为例，即框架充当 WS 服务端，实现端作为 WS 客户端连接到框架。

这里以 Walle-Q 实现端为例，在实际使用中，你可以自由选用不同的实现端。

你可以前往 Walle-Q 的 [发布页面](https://github.com/onebot-walle/walle-q/releases) 下载最新的发行版本，并运行以进行初始化。

在登录成功后，请关闭 Walle-Q 以修改配置文件。

首次运行后，将会在当前目录下生成 `walle-q.toml` 文件。在大多数场景下，这是你唯一需要接触的文件。

在于框架对接的情况中，我们只需要关注 `onebot.websocket_rev` 配置。

```toml {7}
[onebot]
http = []
http_webhook = []
websocket = []

[[onebot.websocket_rev]]
url = "ws://127.0.0.1:20001" # 这里是框架的监听地址
reconnect_interval = 4
```

修改完成并保存后，重新启动 Walle-Q 并登录即可。如果出现连接失败也请勿惊慌，因为框架此时尚未启动，失败是正常现象。

有些情况可能无法正常扫码登录，可使用 QQ+密码 的方式登录，在最上方插入配置：

```toml
[qq.123456]
password = "MyPassword"
```

> 请将 123456 替换为你的机器人 QQ 号码，MyPassword 替换为机器人 QQ 密码。

## 编写第一个功能

在框架中，几乎所有事件的绑定都是通过注解进行的，详情可以参阅 注解的使用。

让我们新建第一个插件，插件的功能很简单，就是复读。我们假设这个复读插件的名字是 `repeater`

```bash
./zhamao plugin:make
# 然后根据提示，创建，比如名字输入 repeater
# 选择类型的时候，输入 file
```

我们就可以在目录 `plugins/repeater/` 下得到两个文件，其中 `main.php` 代码可能如下：

```php
<?php

declare(strict_types=1);

$plugin = new ZMPlugin(__DIR__);

/*
 * 发送 "测试repeater"，回复 "这是repeater插件的第一个命令！"
 */
$cmd1 = BotCommand::make('repeater', match: '测试repeater')->on(fn () => '这是repeater插件的第一个命令！');

$plugin->addBotCommand($cmd1);

return $plugin;
```

然后根据复读的原理（简单重复一遍用户发的消息），将上方 `$cmd1` 替换为下面的指令：

```php
$cmd1 = BotCommand::make('repeater', match: '复读')->on(function(OneBotEvent $event, BotContext $ctx) {
    $ctx->reply($event->getMessage());
});
```

此后，保存文件。

> 借助容器的依赖注入功能，我们可以直接指定相应的类，相关实例会在调用时自动传入。上方的 OneBotEvent 和 BotContext 可以自由选择位置。

## 启动框架

在保存了上述的代码后，你就可以通过 `./zhamao server` 启动框架了。

启动后，Walle-Q 的日志应当会显示连接成功的信息。

此时，你可以通过任意账号向机器人发送 `复读 给我复读` 消息，机器人会回复 `复读 给我复读`。

至此，你的第一个功能，复读机，也就开发完成了。

<chat-box :my-chats="[
{type:0,content:'复读 给我复读'},
{type:1,content:'复读 给我复读'},
]"></chat-box>

## 使用机器人 API 和更多事件

如果你希望机器人进行其他复杂的动作（操作），请参见 机器人上下文。

关于更多可以注册绑定的事件，请参见 注解事件。
