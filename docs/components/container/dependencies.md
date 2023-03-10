# 内置依赖项

在不同的事件（或周期中），可用的依赖项可能会有所不同。例如，当你在一个命令方法中可以使用 `BotContext` 依赖，但在一个路由方法中却不能使用。

你也可以选择直接查看 `src/ZM/Container/ContainerRegistrant.php` 文件，大部分依赖都在该处定义。
GitHub 链接：https://github.com/zhamao-robot/zhamao-framework/blob/main/src/ZM/Container/ContainerRegistrant.php

本页面列出在不同事件中可用的依赖项。

## 全局依赖

在任何事件（或任何支持依赖注入的地方）中，你都可以使用以下依赖项：

- `Psr\Log\LoggerInterface`：日志记录器（可使用类的别名 `LoggerInterface`）
- `Psr\Container\ContainerInterface`：容器（可使用别名 `ContainerInterface`）
- `DI\Container`：容器，区别在于可以使用 `set` 方法来动态设置依赖项，与 `container` 函数返回的实例相同
- `ZM\Config\ZMConfig`：配置，与 `config` 函数返回的实例相同（可使用别名 `ZMConfig`）
- ...

## OneBot 事件

在 OneBot 事件（`@BotEvent`）中，你可以使用以下依赖项：

- `OneBot\V12\Object\OneBotEvent`：当前事件的实例（可使用别名 `OneBotEvent`）
- `ZM\Context\BotContext`：当前事件的上下文，可使用别名 `BotContext`，部分事件可能不可用（要求传入的事件存在 `platform` 字段）

## OneBot 动作响应

在 OneBot 动作响应（`@BotActionResponse`）中，你可以使用以下依赖项：

- `OneBot\V12\Object\ActionResponse`：当前动作响应的实例（可使用别名 `ActionResponse`）

## HTTP 请求事件（路由事件）

在 HTTP 请求事件（`@Route`）中，你可以使用以下依赖项：

- `OneBot\Driver\Event\Http\HttpRequestEvent`：当前事件的实例（可使用别名 `HttpRequestEvent`）
- `Psr\Http\Message\ServerRequestInterface`：当前请求的实例（可使用别名 `ServerRequestInterface`）

## WebSocket 连接事件

在 WebSocket 连接事件（`@BindEvent(WebSocketOpenEvent::class)`）中，你可以使用以下依赖项：

- `OneBot\Driver\Event\WebSocket\WebSocketOpenEvent`：当前事件的实例（可使用别名 `WebSocketOpenEvent`）

## WebSocket 消息事件

在 WebSocket 消息事件（`@BindEvent(WebSocketMessageEvent::class)`）中，你可以使用以下依赖项：

- `OneBot\Driver\Event\WebSocket\WebSocketMessageEvent`：当前事件的实例（可使用别名 `WebSocketMessageEvent`）
- `Choir\WebSocket\FrameInterface`：当前消息（帧）的实例（可使用别名 `FrameInterface`）

## WebSocket 关闭事件

在 WebSocket 关闭事件（`@BindEvent(WebSocketCloseEvent::class)`）中，你可以使用以下依赖项：

- `OneBot\Driver\Event\WebSocket\WebSocketCloseEvent`：当前事件的实例（可使用别名 `WebSocketCloseEvent`）
