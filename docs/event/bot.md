# 机器人事件

<aside>
🛰️ 此页面下的所有注解命名空间为 `ZM\Annotation\OneBot`

</aside>

> 在使用注解绑定事件时，如果不存在 **必需** 参数，可一个参数都不写，效果就是此事件在任何情况下都会调用此方法，例如 `#[BotEvent()]` 会在收到任意机器人事件时调用。


## BotAction

BotAction 注解将在 OneBot 12 标准的动作发送前会触发，体现在代码层面就是在使用机器人上下文 `ctx()->sendAction()` 方法时会触发。

| 参数名称          | 允许值    | 用途            | 默认    |
|---------------|--------|---------------|-------|
| action        | string | 动作名称          | “”    |
| need_response | string | 动作是否需要响应      | false |
| level         | int    | 事件优先级（越大越先执行） | 20    |

举例一，你可以通过设置一个 BotAction 注解事件，来收集和统计所有机器人发出的消息、执行的动作：

```php
#[BotAction()]
public function onBotAction(\OneBot\V12\Object\Action $action)
{
    logger()->info('机器人执行了动作：' . $action->action);
}
```

举例二，你可以通过设置 BotAction 注解的限定参数来限定捕获触发的动作事件：

```php
// 限定只获取 send_message 动作的触发
#[BotAction(action: 'send_message')]
public function onSendMessage(\OneBot\V12\Object\Action $action)
{
    logger()->info('机器人发送了消息：' . \ZM\Utils\MessageUtil::getAltMessage($action->params['message']));
}
```

举例三，你可以通过 `need_response` 参数来限定 BotAction 触发的时机。默认情况下，BotAction 在调用 `ctx()->sendAction()` 后立刻触发，
如果限定 `need_response: true`，该事件将会在动作收到响应后再触发，届时你可以通过依赖注入的方式，获取 ActionResponse 对象：

```php
#[BotAction(need_response: true)]
public function onActionWithResponse(\OneBot\V12\Object\Action $action, \OneBot\V12\Object\ActionResponse $response)
{
    logger()->info('机器人发送了动作：' . $action->action . '，并且返回状态码为 ' . $response->retcode);
}
```

## BotActionResponse

BoActionResponse 注解将在 OneBot 12 标准的动作发出，并收到了合法的响应内容时触发。

| 参数名称      | 允许值    | 用途             | 默认    |
|-----------|--------|----------------|-------|
| status    | string | 用于限定成功与否的状态    | null  |
| retcode   | int    | 响应码            | null  |
| level     | int    | 事件优先级（越大越先执行）  | 20    |

举例一，你需要获取所有响应不成功的动作，则只需设置 status 为 failed 即可：

```php
#[BotActionResponse(status: 'failed')]
public function onFailedResponse(\OneBot\V12\Object\ActionResponse $response)
{
    logger()->error('动作请求失败，错误码：' . $response->retcode. '，错误消息：' . $response->message);
}
```

如果你的机器日代码逻辑更偏向于关注单个动作请求的成功与否，
这里其实更推荐使用上方的 `BotAction` 注解，并采用 `need_response: true` 参数，这样可以同时使用 Action 和 ActionResponse 对象。

## BotEvent

用于处理所有的机器人事件，具体的参数含义可以参见 [https://12.onebot.dev/connect/data-protocol/event/](https://12.onebot.dev/connect/data-protocol/event/)。

| 参数名称        | 允许值    | 用途            | 默认   |
|-------------|--------|---------------|------|
| type        | string | 对应标准中的事件类型    | null |
| detail_type | string | 对应标准中的事件详细类型  | null |
| sub_type    | string | 对应标准中的事件子类型   | null |
| level       | int    | 事件优先级（越大越先执行） | 20   |

除了 level 外的参数，均可做限定事件内容的参数。

举例一，你想写一个事件注解绑定的方法，但只获取 `type` 为 `notice` 消息类的事件：

```php
#[BotEvent(type: 'notice')]
public function onNotice(BotContext $ctx, OneBotEvent $event)
{
    logger()->info('收到了机器人 ' . $event->self['user_id'] . ' 的通知事件，子类型为 ' . $event->detail_type);
}
```

举例二，你想限定获取群所有群消息，通过设置 `type`、`detail_type` 两个参数组合来获取：

```php
#[BotEvent(type: 'message', detail_type: 'group')]
public function onGroupMessage(OneBotEvent $event)
{
    // getAltMessage() 为返回一个终端可读的展示型文本，非消息原文
    logger()->info('来自群组 ' . $event->getGroupId() . ':' . $event->getUserId() . ' 的消息：' . $event->getAltMessage());
}
```

## BotCommand

对于 `BotEvent` 的封装，用于支持常用的命令式调用（如：”天气 深圳”）。

| 参数名称        | 允许值             | 用途                           | 默认  |
|-------------|-----------------|------------------------------|-----|
| name        | string          | 命令名称，应全局唯一                   | “”  |
| match       | string          | 匹配第一个词的命令式消息，如 天气 北京 中的 天气   | “”  |
| pattern     | string          | 根据 * 号通配符进行模式匹配用户消息，如 查询*天气  | “”  |
| regex       | string 合法的正则表达式 | 匹配正则表达式匹配到的用户消息              | “”  |
| start_with  | string          | 匹配消息开头相匹配的消息，如 我叫炸毛，这里写 我叫   | “”  |
| end_with    | string          | 匹配消息结尾相匹配的消息，以 start_with 类推 | “”  |
| keyword     | string          | 匹配消息中有相关关键词的消息               | “”  |
| alias       | string          | match 匹配到命令的别名，数组形式          | []  |
| detail_type | string          | 限定消息事件的详细类型，见 BotEvent       | “”  |
| prefix      | string          |                              |     |
| level       | int             | 事件优先级（越大越先执行）                | 20  |

> 机器人命令注册的实例可参见【一堆例子链接】

## CommandArgument

与 BotCommand 搭配使用，可以自动识别参数及生成帮助。

| 参数名称        | 允许值     | 用途                           | 默认           |
|-------------|---------|------------------------------|--------------|
| name        | string  | 参数名称                         | “”           |
| description | string  | 参数描述                         | “”           |
| required    | bool    | 参数是否必需                       | false        |
| prompt      | string  | 当参数缺失时请求用户输入时提示的消息（需要参数设为必需） | “请输入{$name}” |
| default     | mixed   | 当参数非必需且未填入时的默认值              | null         |
| timeout     | int 单位秒 | 请求输入超时时间                     | 60           |

## CommandHelp

与 BotCommand 搭配使用，可以补充生成的帮助信息。

| 参数名称        | 允许值    | 用途   | 默认  |
|-------------|--------|------|-----|
| description | string | 命令描述 | “”  |
| usage       | string | 命令用法 | “”  |
| example     | string | 命令示例 | “”  |

> 关于自动帮助生成的更多信息，请参见 【这里链接】
