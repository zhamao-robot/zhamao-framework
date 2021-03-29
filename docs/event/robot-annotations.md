# 机器人注解事件

QQ 机器人事件是指 CQHTTP 插件发来的 Event 事件，被框架处理后触发到单个类中方法的事件。

为了便于开发，这里的注解类对应 CQHTTP 插件返回的 `post_type` 类型，对号入座即可。

!!! tip "提示"

	在使用注解绑定事件过程中，如果无 **必需** 参数，可一个参数也不写，效果就是此事件任何情况下都会调用此方法。例如：`@CQMessage()` 

事件是用户需要从 OneBot 被动接收的数据，有以下几个大类：

- [消息事件](#cqmessage)，包括私聊消息、群消息等，被 [`@CQCommand`](#cqcommand)，`@CQMessage` 注解处理。

- [通知事件](#cqnotice)，包括群成员变动、好友变动等，被 `@CQNotice` 注解事件处理。

- [请求事件](#cqrequest)，包括加群请求、加好友请求等，被 `@CQRequest` 注解事件处理。

- [元事件](#cqmetaevent)，包括 OneBot 生命周期、心跳等，被 `@CQMetaEvent` 注解事件处理。

## 注解事件参照表

| 注解名称                                                | 类所在命名全称               | 作用                                                         |
| ------------------------------------------------------- | ---------------------------- | ------------------------------------------------------------ |
| [`@CQBefore`](/event/robot-annotations/#cqbefore)       | `\ZM\Annotation\CQBefore`    | OneBot 各类事件前触发的，可当作事件过滤器使用                |
| [`@CQAfter`](/event/robot-annotations/#cqafter)         | `\ZM\Annotation\CQAfter`     | OneBot 各类事件后触发的                                      |
| [`@CQMessage`](/event/robot-annotations/#cqmessage)     | `\ZM\Annotation\CQMessage`   | OneBot 中消息类事件的触发（机器人消息）事件                  |
| [`@CQCommand`](/event/robot-annotations/#cqcommand)     | `\ZM\Annotation\CQCommand`   | OneBot 中消息类事件的触发（机器人消息）事件，但是被封装为指令型的，无需自己切割命令式 |
| [`@CQNotice`](/event/robot-annotations/#cqnotice)       | `\ZM\Annotation\CQNotice`    | OneBot 中通知类事件的触发（机器人消息）事件                  |
| [`@CQRequest`](/event/robot-annotations/#cqrequest)     | `\ZM\Annotation\CQRequest`   | OneBot 中请求类事件的触发（机器人消息）事件，一般带有请求信息，可联动相关响应的 API 完成功能编写 |
| [`@CQMetaEvent`](/event/robot-annotations/#cqmetaevent) | `\ZM\Annotation\CQMetaEvent` | OneBot 中涉及 OneBot 实现本身的一些和机器人事件无关的元事件，比如 WS 连接的心跳包 |

## CQMessage()

QQ 收到消息后触发的事件对应注解。

### 属性

| 类型       | 值 |
| ---- | ----------- |
| 名称 | `@CQMessage` |
| 触发前提 | 当 `post_type` 为 `message` 时触发 |
| 命名空间 | `ZM\Annotation\CQ\CQMessage` |
| 适用位置 | 方法 |
| 返回值处理 | 当方法返回字符串时，效果等同于执行 `ctx()->reply("xxx")` |

### 参数

| 参数名称     | 参数范围                              | 用途                                   | 默认 |
| ------------ | ------------------------------------- | -------------------------------------- | ---- |
| message_type | `string`，支持填入 `private`，`group` | 限定消息事件的来源类型，如私聊或群消息 | 空   |
| user_id      | `int64` 或 `string`                   | 限定消息发送用户 ID（QQ 号）           | 空   |
| group_id     | `int64` 或 `string`                   | 限定消息发送来源群 ID（QQ 群号）       | 空   |
| message      | `string`                              | 限定消息内容文本                       | 空   |
| level        | `int`                                 | 事件优先级（越大越靠前）               | 20   |

### 用法

下面这个例子的注释用途就是：

- 在用户 QQ 为 `123456` 的用户私聊给机器人发消息后机器人回复内容。
- 用户发送文字为 `hello` 时返回 `你好啊，xxx` 的消息。

===  "代码"

	```php
	<?php
	namespace Module\Example;
	
	use ZM\Annotation\CQ\CQMessage;
	
	class Hello {
	    /**
	     * @CQMessage(message_type="private",user_id=123456)
	     */
	  	public function test() {
	        return "你和机器人私聊发送了这些文本：".ctx()->getMessage();
	    }
	    /**
	     * @CQMessage(message="hello")
	     */
	    public function hello() {
	        return "你好啊，".ctx()->getUserId();
	    }
	}
	```

=== "效果"

	<chat-box>
	) 假设我是私聊机器人
	( 你和机器人私聊发送了这些文本：假设我是私聊机器人
	^ 假设我现在切到群里，在群里发hello
	) hello
	( 你好啊，123456
	</chat-box>

## CQCommand()

此注解是对 `@CQMessage` 类别的再封装，是命令解析格式处理消息的利器。例如，你想写一个疫情上报，指令是 `疫情 城市名称`，那么此方式来解析用户消息会更加方便。

### 属性

| 类型       | 值                                                       |
| ---------- | -------------------------------------------------------- |
| 名称       | `@CQCommand`                                             |
| 触发前提   | 当根据参数规则匹配到用户命令式消息时触发                 |
| 命名空间   | `ZM\Annotation\CQ\CQCommand`                             |
| 适用位置   | 方法                                                     |
| 返回值处理 | 当方法返回字符串时，效果等同于执行 `ctx()->reply("xxx")` |

### 参数

| 参数名称     | 参数范围                              | 用途                                                   | 默认 |
| ------------ | ------------------------------------- | ------------------------------------------------------ | ---- |
| match        | `string`                              | 匹配第一个词的命令式消息，如 `天气 北京` 中的 `天气`   | 空   |
| pattern      | `string`                              | 根据 * 号通配符进行模式匹配用户消息，如 `查询*天气`    | 空   |
| regex        | `string`，限定正则表达式              | 匹配正则表达式匹配到的用户消息                         | 空   |
| start_with   | `string`                              | 匹配消息开头相匹配的消息，如 `我叫炸毛`，这里写 `我叫` | 空   |
| end_with     | `string`                              | 匹配消息结尾相匹配的消息，以 `start_with` 类推         | 空   |
| keyword      | `string`                              | 匹配消息中有相关关键词的消息                           | 空   |
| alias        | `array[string]`                       | `match` 匹配到命令的别名，数组形式                     | `{}` |
| message_type | `string`，支持填入 `private`，`group` | 限定消息事件的来源类型，同 `@CQMessage`                | 空   |
| user_id      | `int64` 或 `string`                   | 限定消息发送用户 ID，同 `@CQMessage`                   | 空   |
| group_id     | `int64` 或 `string`                   | 限定消息发送来源群 ID，同 `@CQMessage`                 | 空   |
| level        | `int`                                 | 事件优先级（越大越靠前）                               | 20   |

!!! warning "注意"

	在 `@CQCommand` 注解事件中，从 `match` 到 `keyword` 六个参数中，必须且只能定义一个，`alias` 目前只能和 `match` 参数同时使用；
	
	框架内部对于同一条消息事件，优先处理 `@CQCommand` 注解事件，如果未匹配到任何注解事件，则才会继续执行 `@CQMessage` 注解事件。

- 参数 `match` 匹配模式是：遇到空格、换行就会切分，比如 `点歌 xxx yyy` 会被分割为 `[点歌,xxx,yyy]`，然后抽取第一个词做为命令去匹配，剩下的为参数。
- 参数 `pattern` 匹配模式是：\* 号位置变成参数，比如 `从*到*的随机数`，我们输入 `从1到9的随机数`，成功匹配，参数列表：`[1,9]`。
- 参数 `regex` 匹配模式为 PHP 标准的 pcre 正则表达式，比如 `([01][0-9][2][0-3]):[0-5][0-9]` 用来匹配 `22:45`。
- 参数 `start_with`， `end_with` 和 `keyword` 都是根据消息内容开头、结尾或者内容包含是否匹配来匹配，这里就不多说了，你懂的。
- 参数 `alias` 用的时候一般是这样：`@CQCommand(match="你好",alias={"你好啊","你是谁"})`，用以扩充同义词下命令的适配广度。

### 用法

我们以参数 `match` 写一个简单的 demo：

=== "代码"
	```php
	<?php
	namespace Module\Example;

	use ZM\Annotation\CQ\CQCommand;
	
	class Hello {
	    /**
	     * @CQCommand(match="疫情",alias={"COVID"})
	     */
	    public function virus(){
	        $city = ctx()->getNextArg("请输入城市名称");
	        return "城市 ".$city." 的疫情状况如下："."{这里假装是疫情接口返回的数据}";
	    }
	    /**
	     * 如果选择使用 match 参数的话，可以省略 `match=`
	     * @CQCommand("掷硬币")
	     */
	    public function randChoice() {
	        return "你看到的是：" . (mt_rand(0,1) ? "正面" : "反面");
	    }
	    /**
	     * @CQCommand(pattern="*把*翻译成*")
	     */
	    public function translate() {
	        ctx()->getNextArg(); // 为什么需要单独调用一次呢？看下面例子就知道啦
	        $text = ctx()->getNextArg(); // 获取第二个星号匹配的内容
	        $target = ctx()->getNextArg(); // 获取第三个星号匹配的内容
	        // 这里 FakeTranslateAPI 是假设我们对接了一个翻译的 API，开发时请替换为自己的接口。
	        return "翻译结果：" . FakeTranslateAPI::translate($text, $target);
	    }
	}
	```
=== "效果"

	<chat-box>
	) 疫情 北京
	( 城市 北京 的疫情状况如下：blablablabla
	) COVID 香港
	( 城市 香港 的疫情状况如下：blablablabla
	) 掷硬币
	( 你看到的是：正面
	) 我想把我爱你翻译成英语
	( 翻译结果：I love you!
	</chat-box>

## CQNotice()

通知事件。

### 属性

| 类型       | 值                                                    |
| ---------- | ----------------------------------------------------- |
| 名称       | `@CQNotice`                                           |
| 触发前提   | 当 `post_type` 为 `notice` 时触发（通知类事件上报时） |
| 命名空间   | `ZM\Annotation\CQ\CQNotice`                           |
| 适用位置   | 方法                                                  |
| 返回值处理 | 无作用                                                |

### 参数

| 参数名称    | 参数范围                             | 用途                                                         | 默认 |
| ----------- | ------------------------------------ | ------------------------------------------------------------ | ---- |
| notice_type | `string`，支持填入 onebot 标准的内容 | 限定通知事件的类型，见 [OneBot - 通知事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/notice.md) | 空   |
| user_id     | `int64` 或 `string`                  | 限定通知事件用户 ID（QQ 号），同上见 [OneBot - 通知事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/notice.md) | 空   |
| group_id    | `int64` 或 `string`                  | 限定通知事件群 ID（QQ 群号），同上见 [OneBot - 通知事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/notice.md) | 空   |
| operator_id | `int64` 或 `string`                  | 限定操作者 QQ 号，同上见 [OneBot - 通知事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/notice.md) | 空   |
| level       | `int`                                | 事件优先级（越大越靠前）                                     | 20   |

### 用法

TODO：先放着，有时间再更。

## CQRequest()

请求事件。

### 属性

| 类型       | 值                                                     |
| ---------- | ------------------------------------------------------ |
| 名称       | `@CQRequest`                                           |
| 触发前提   | 当 `post_type` 为 `request` 时触发（通知类事件上报时） |
| 命名空间   | `ZM\Annotation\CQ\CQRequest`                           |
| 适用位置   | 方法                                                   |
| 返回值处理 | 无作用                                                 |

### 参数

| 参数名称     | 参数范围                             | 用途                                                         | 默认 |
| ------------ | ------------------------------------ | ------------------------------------------------------------ | ---- |
| request_type | `string`，支持填入 onebot 标准的内容 | 限定请求事件的类型，见 [OneBot - 请求事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/request.md) | 空   |
| user_id      | `int64` 或 `string`                  | 限定请求事件当事人用户 ID（QQ 号），见 [OneBot - 请求事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/request.md) | 空   |
| sub_type     | `string`                             | 限定请求事件来源群 ID（QQ 群号），见 [OneBot - 请求事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/request.md) | 空   |
| comment      | `string`                             | 限定验证消息内容，见 [OneBot - 请求事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/request.md) | 空   |
| level        | `int`                                | 事件优先级（越大越靠前）                                     | 20   |

### 用法

TODO：先放着，有时间再更。

## CQMetaEvent()

元事件，元事件不属于用户交互的一部分，消息、通知、请求三大类事件是与聊天软件直接相关的、机器人真实接收到的事件，除了这些，OneBot 自己还会产生一类事件，这里称之为「元事件」，例如生命周期事件、心跳事件等，这类事件与兼容 OneBot 的客户端和炸毛框架本身的运行状态有关，而与聊天软件无关。元事件的上报方式和普通事件完全一样。

### 属性

| 类型       | 值                                                    |
| ---------- | ----------------------------------------------------- |
| 名称       | `@CQMetaEvent`                                        |
| 触发前提   | 当 `post_type` 为 `meta_event` 时触发（元事件上报时） |
| 命名空间   | `ZM\Annotation\CQ\CQMetaEvent`                        |
| 适用位置   | 方法                                                  |
| 返回值处理 | 无作用                                                |

### 参数

| 参数名称        | 参数范围           | 用途                                                         | 默认 |
| --------------- | ------------------ | ------------------------------------------------------------ | ---- |
| meta_event_type | `string`，**必需** | 限定元事件的类型，见 [OneBot - 元事件](https://github.com/howmanybots/onebot/blob/master/v11/specs/event/meta.md) |      |
| level           | `int`              | 事件优先级（越大越靠前）                                     | 20   |

### 用法

TODO：先放着，有时间再更。

## CQBefore()

所有机器人事件的前置注解事件，一般用作消息过滤、全局日志、全局替换等。

### 属性

| 类型       | 值                                                           |
| ---------- | ------------------------------------------------------------ |
| 名称       | `@CQBefore`                                                  |
| 触发前提   | 当 `post_type` 等于参数 `cq_event` 时触发                    |
| 命名空间   | `ZM\Annotation\CQ\CQBefore`                                  |
| 适用位置   | 方法                                                         |
| 返回值处理 | 仅可返回 `bool`，如果为 `false`，则阻断 `cq_event` 类的所有事件防止被执行 |

### 参数

| 参数名称 | 参数范围                                                     | 用途                     | 默认 |
| -------- | ------------------------------------------------------------ | ------------------------ | ---- |
| cq_event | `string`，**必需**，支持 `message`，`notice`，`request`，`meta_event` | 限定机器人时间的类型     |      |
| level    | `int`                                                        | 事件优先级（越大越靠前） | 20   |

### 用法

=== "代码"

	```php
	<?php
	namespace Module\Example;
	
	use ZM\Annotation\CQ\CQBefore;
	use ZM\Annotation\CQ\CQMessage;
	class Test {
	    /**
	     * @CQBefore("message")
	     */
	    public function filter(){
	        // 可用于敏感词，如政治相关的词语不响应其他模块
	        if(mb_strpos(ctx()->getMessage(), "谷歌") !== false) return false;
	        else return true;
	    }
	    /**
	     * @CQCommand("百科")
	     */
	    public function wiki() {
	        $content = ctx()->getNextArg("请说你要查百科的内容");
	        // 这里假设你对接了一个查百科的接口
	        return "已搜到匹配 $content 的如下结果：".FakeAPI::searchWiki($content);
	    }
	}
	```

=== "效果"

	<chat-box>
	) 百科 北京
	( 已搜到匹配 北京 的如下结果：blablabla
	) 百科 谷歌被封
	^ 机器人没有任何回复

!!! warning "注意"

	在设置了 `level` 参数后，如果设置了多个 `@CQBefore` 监听事件函数，更高 `level` 的事件函数返回了 `false`，则低 `level` 的绑定函数不会执行，所有 `@CQMessage` 绑定的事件也不会执行。
	
	你也可以使用 `@CQBefore` 做一些消息的转发和过滤。比如你想去除用户发来的文字中的 emoji、图片等 CQ 码，只保留文本。
	
	使用 `ctx()->waitMessage()` 时等待用户输入下一条消息功能和 CQBefore 配合过滤消息时需注意，见 [FAQ - CQBefore 过滤不了 waitMessage](/FAQ/wait-message-cqbefore/)

## CQAfter()

同上。只是在以上所有事件都调用后才会调用的。

## CQAPIResponse()

TODO：还没写完，先放着，有时间再更。
