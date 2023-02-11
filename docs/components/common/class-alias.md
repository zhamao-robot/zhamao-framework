# 类全局别名

在框架 1.x 和 2.x 老版本中，我们发现许多开发者在使用框架时，往往不会使用 PhpStorm 这类大型 IDE，而即使使用 VSCode 这类编辑器的时候也不一定会安装补全插件，
这样在编写机器人模块或插件时会因寻找每个对象的完整命名空间而烦恼。

在 3.0 版本起，框架对常用的注解事件和对象均使用了类别名功能，方便非 IDE 开发者编写插件。

## 别名使用

框架对别名的定义比较简单，由于内部暂时没有不同命名空间下重复类名的情况，所以我们目前只对需要别名类名的命名空间移除，例如：

`\ZM\Annotation\OneBot\BotCommand` 注解事件类，在经过全局别名后，你也可以使用 `\BotCommand` 作为注解事件，效果相同。

## 别名列表
| 全类名                                                    | 别名                      |
|--------------------------------------------------------|-------------------------|
| `\ZM\Annotation\Framework\BindEvent`                   | `BindEvent`             |
| `\ZM\Annotation\Framework\Cron`                        | `Cron`                  |
| `\ZM\Annotation\Framework\Init`                        | `Init`                  |
| `\ZM\Annotation\Framework\Setup`                       | `Setup`                 |
| `\ZM\Annotation\Framework\Tick`                        | `Tick`                  |
| `\ZM\Annotation\Http\Controller`                       | `Controller`            |
| `\ZM\Annotation\Http\Route`                            | `Route`                 |
| `\ZM\Annotation\Middleware\Middleware`                 | `Middleware`            |
| `\ZM\Annotation\OneBot\BotAction`                      | `BotAction`             |
| `\ZM\Annotation\OneBot\BotActionResponse`              | `BotActionResponse`     |
| `\ZM\Annotation\OneBot\BotCommand`                     | `BotCommand`            |
| `\ZM\Annotation\OneBot\BotEvent`                       | `BotEvent`              |
| `\ZM\Annotation\OneBot\CommandArgument`                | `CommandArgument`       |
| `\ZM\Annotation\OneBot\CommandHelp`                    | `CommandHelp`           |
| `\ZM\Annotation\Closed`                                | `Closed`                |
| `\ZM\Plugin\ZMPlugin`                                  | `ZMPlugin`              |
| `\ZM\Context\BotContext`                               | `BotContext`            |
| `\ZM\Utils\ZMRequest`                                  | `ZMRequest`             |
| `\ZM\Store\KV\LightCache`                              | `LightCache`            |
| `\ZM\Store\KV\Redis\KVRedis`                           | `KVRedis`               |
| `\OneBot\Driver\Event\WebSocket\WebSocketOpenEvent`    | `WebSocketOpenEvent`    |
| `\OneBot\Driver\Event\WebSocket\WebSocketCloseEvent`   | `WebSocketCloseEvent`   |
| `\OneBot\Driver\Event\WebSocket\WebSocketMessageEvent` | `WebSocketMessageEvent` |
| `\OneBot\Driver\Event\Http\HttpRequestEvent`           | `HttpRequestEvent`      |
| `\OneBot\V12\Object\OneBotEvent`                       | `OneBotEvent`           |
| `\Choir\Http\HttpFactory`                              | `HttpFactory`           |
