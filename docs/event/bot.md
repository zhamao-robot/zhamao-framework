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

}
```

## BotActionResponse

啊？？

| 参数名称    | 允许值 | 用途            | 默认   |
|---------|-----|---------------|------|
| retcode | int | 响应码           | null |
| level   | int | 事件优先级（越大越先执行） | 20   |

## BotEvent

用于处理所有的机器人事件，具体的参数含义可以参见 [https://12.onebot.dev/connect/data-protocol/event/](https://12.onebot.dev/connect/data-protocol/event/)。

| 参数名称        | 允许值    | 用途            | 默认   |
|-------------|--------|---------------|------|
| type        | string | 对应标准中的事件类型    | null |
| detail_type | string | 对应标准中的事件详细类型  | null |
| sub_type    | string | 对应标准中的事件子类型   | null |
| level       | int    | 事件优先级（越大越先执行） | 20   |

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
>

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
