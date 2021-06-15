# 图灵机器人 API（TuringAPI）

类定义：`\ZM\API\TuringAPI`

## 方法

### TuringAPI::getTuringMsg()

请求图灵接口，返回回复的消息。

定义：`getTuringMsg($msg, $user_id, $api)`

参数 `$msg` 为用户的消息内容，如果含有图片 CQ 码，则自动转换为图灵兼容的接口模式。

参数 `$user_id` 为用户 ID，一般默认给 QQ 号码就可以了，注意最好不要有特殊字符（如 `./\<>*` 等），否则会间断性调用失败。

参数 `$api` 为图灵机器人的 `apikey`，可以到 <http://www.turingapi.com/> 申请免费或付费的 API key。

在框架的示例模块中，已经写好了一个正常机器人响应图灵回复的命令，如下：

```php
class Hello {
    /**
     * 图灵机器人的内置实现，在www.turingapi.com申请一个apikey填入下方变量即可。
     * @CQCommand(start_with="机器人",end_with="机器人",message_type="group")
     * @CQMessage(message_type="private",level=1)
     */
    public function turingAPI() {
        $user_id = ctx()->getUserId();
        $api = ""; // 请在这里填入你的图灵机器人的apikey
        if ($api === "") return false; //如果没有填入apikey则此功能关闭
        if (($this->_running_annotation ?? null) instanceof CQCommand) {
            $msg = ctx()->getFullArg("我在！有什么事吗？");
        } else {
            $msg = ctx()->getMessage();
        }
        ctx()->setMessage($msg);
        if (MessageUtil::matchCommand($msg, ctx()->getData())->status === false) {
            return TuringAPI::getTuringMsg($msg, $user_id, $api);
        } else {
            QQBot::getInstance()->handle(ctx()->getData(), ctx()->getCache("level") + 1);
            return true;
        }
    }

    /**
     * 响应at机器人的消息
     * @CQBefore("message")
     */
    public function changeAt() {
        if (MessageUtil::isAtMe(ctx()->getMessage(), ctx()->getRobotId())) {
            $msg = str_replace(CQ::at(ctx()->getRobotId()), "", ctx()->getMessage());
            ctx()->setMessage("机器人" . $msg);
            Console::info(ctx()->getMessage());
        }
        return true;
    }
}
```

如上述代码，我们将申请的 apikey 填入变量 `$api` 中，启动机器人即可使用，以下是实测消息（我用自己申请的 key 做测试回复的消息）。

<chat-box>
) 你咋了
( 我没事哦，谢谢您的关心。
) 上海天气
( 上海:周一 03月29日,小雨 东南风转东风,最低气温14度，最高气温24度。
^ 切换为群内
) 机器人
( 我在！有什么事吗？
) 你叫啥
( 我的名字叫炸毛，认识你很高兴呢！
</chat-box>

在默认示例模块中的例子是直接可以拿来用的，这段代码同时做了对 at 的处理、以及兼容用户自定义写的其他命令的方式，下面是默认模块填好 apikey 后可以用的各种方式提问：

<chat-box>
^ 切换为群内
) 我是一条普通消息，这条机器人不会回复我
) @机器人 你叫啥
( 我是聪明可爱的炸毛，认识你很高兴。
) 机器人
( 我在！有什么事吗？
) 一言
( 多少事，从来急，天地转，光阴迫，一万年太久，只争朝夕。
</chat-box>