# 编写管理员专属功能

众所周知，如果大家使用炸毛框架来开发聊天机器人的话，会比较方便。但是有些地方你一定会感觉还是欠缺了点，比如下面这样，你想编写一个只能由机器人管理员，也就是你自己，才能触发的功能：

```php
/**
 * @CQCommand(match="禁言",message_type="group")
 */
public function banSomeone() {
    $r1 = ctx()->getNextArg("请输入禁言的人或at他");
    $r2 = ctx()->getFullArg("请输入禁言的时间（秒）");
    $cq = CQ::getCQ($r1);
    if ($cq !== null) {
        if ($cq["type"] != "at") return "请at或者输入正确的QQ号！";
        $r1 = $cq["params"]["qq"];
    }
    // 群内禁言用户
    ctx()->getRobot()->setGroupBan(ctx()->getGroupId(), $r1, $r2);
    return "禁言成功！";
}
```

这时候，如果只是自己有绝对的权利，可以将自己的 QQ 号写死在注解 `@CQCommand` 中，并限定 `user_id`（假设我的 QQ 号码为 123456）：

```php
/**
 * @CQCommand(match="禁言",message_type="group",user_id=123456)
 */
```

但是，随着时间的推移，你的机器人伙伴群可能越来越大，这个命令可能不止需要绝对的你来使用，你还要将机器人的部分权利下发给更多的伙伴，怎么办呢？注解里面只能写死的。

答案很简单，这时候我们就需要用到框架提供的中间件（Middleware）。中间件说白了就是在事件执行前、后、过程中抛出的异常对其进行阻断和插入代码，比如我们上方在触发禁言这个注解事件前首先要判断执行这个命令的是不是钦定的管理员。

## 第一步：定义中间件

首先，我们需要定义一个中间件。在框架默认提供的脚手架中，包含了一个叫 `TimerMiddleware.php` 的示例中间件，这个示例中间件的目的是非常简单的，就是判断这个注解事件运行了多长时间。假设你有一个机器人功能，这个功能下的代码需要执行很长时间，可以使用这一注解轻松将事件执行的时间打印到终端上。

关于中间件的有关说明，见 [中间件](/event/middleware)。

下面我们假设你已经阅读过中间件注解的文档了，我们着手编写一个判断指令执行者是否是指定的管理员 QQ 的中间件。为了省事和让大家方便地复现，我先在脚手架下的目录 `src/Module/Middleware/` 下新建 PHP 类文件 `AdminMiddleware.php`（和 `TimerMiddleware.php` 在同一个目录）。

```php
<?php

namespace Module\Middleware;

use ZM\Annotation\Http\HandleBefore;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Exception\ZMException;
use ZM\Http\MiddlewareInterface;
use ZM\Store\LightCache;

/**
 * Class AdminMiddleware
 * 示例中间件：用于动态管理一些管理员指令的中间件
 * @package Module\Middleware
 * @MiddlewareClass("admin")
 */
class AdminMiddleware implements MiddlewareInterface
{
    /**
     * @HandleBefore()
     * @return bool
     * @throws ZMException
     */
    public function onBefore(): bool {
        $r = ctx()->getUserId(); // 从上下文获取发消息的用户 QQ
        $admin_list = LightCache::get("admin_list") ?? []; // 从轻量缓存获取管理员列表
        return in_array($r, $admin_list); // 返回这个 QQ 是否在管理员列表中
    }
}
```

其中，`@MiddlewareClass("admin")` 的意思是，定义这个类为名字叫 `admin` 的中间件，同时，所有中间件的类**必须**带上 `implements MiddlewareInterface`，统一接口形式。

`@HandleBefore()` 代表的是，这个类下的这个函数（onBefore）被标注为这个中间件的 `onBefore` 事件，也就是说，如果有别的注解事件插入了这个 `admin` 中间件，那么执行对应注解事件前都要执行一下 `@HandleBefore` 所绑定的这个函数。而这个绑定的函数只能返回 `bool` 类型的值哦！

## 第二步：使用中间件

使用中间件很简单，在需要阻断的注解事件绑定的函数上再加一个注解就好了！我们以上方的禁言例子说明：

```php
/**
 * @Middleware("admin")
 * @CQCommand(match="禁言",message_type="group")
 */
```

<chat-box :my-chats="[
    {type:0,content:'禁言 1234567 600'},
    {type:1,content:'禁言成功！'},
    {type:2,content:'假设我不在管理员名单里'},
    {type:0,content:'禁言 1234567 900'},
    {type:2,content:'机器人没有回复，因为中间件返回了 false，不继续执行'},
]"></chat-box>

而这时候有朋友又要问了，如果我有一系列管理员命令，假设都在一个叫 `AdminFunc.php` 的模块类里，我是不是还得一个一个地给注解事件写 `@Middleware("admin")` 呢？当然不需要！如果你这个类所有的注解事件都是机器人的聊天事件（`@CQCommand`，`@CQMessage`）的话，可以直接给类注解这个中间件，效果等同于给每一个函数写一次中间件注解。

```php
<?php

namespace Module\Example;

use ZM\Annotation\Http\Middleware;

/**
 * Class AdminFunc
 * @package Module\Example
 * @Middleware("admin")
 */
class AdminFunc
{ 
    // ...这里是你的一堆注解事件的函数
}
```

## 第三步：补全代码

上面我们讲到了，中间件里面使用了 `LightCache` 轻量缓存来储存临时的管理员列表，那么我们将这部分的代码完善吧！

**src/Module/Example/AdminFunc.php**

```php
<?php

namespace Module\Example;

use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Http\Middleware;
use ZM\API\CQ;

/**
 * Class AdminFunc
 * @package Module\Example
 * @Middleware("admin")
 */
class AdminFunc
{
    /**
     * @CQCommand(match="禁言",message_type="group")
     */
    public function banSomeone() {
        $r1 = ctx()->getNextArg("请输入禁言的人或at他");
        $r2 = ctx()->getFullArg("请输入禁言的时间（秒）");
        $cq = CQ::getCQ($r1);
        if ($cq !== null) {
            if ($cq["type"] != "at") return "请at或者输入正确的QQ号！";
            $r1 = $cq["params"]["qq"];
        }
        // 群内禁言用户
        ctx()->getRobot()->setGroupBan(ctx()->getGroupId(), $r1, $r2);
        return "禁言成功！";
    }

    /**
     * @CQCommand(match="解除禁言",message_type="group")
     */
    public function unbanSomeone() {
        $r1 = ctx()->getNextArg("请输入禁言的人或at他");
        $cq = CQ::getCQ($r1);
        if ($cq !== null) {
            if ($cq["type"] != "at") return "请at或者输入正确的QQ号！";
            $r1 = $cq["params"]["qq"];
        }
        // 群内禁言用户
        ctx()->getRobot()->setGroupBan(ctx()->getGroupId(), $r1, 0);
        return "解除禁言成功！";
    }
}
```

**src/Module/Example/AdminManager.php**

```php
<?php

namespace Module\Example;

use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Http\Middleware;
use ZM\Annotation\Swoole\OnStart;
use ZM\Store\LightCache;
use ZM\Store\Lock\SpinLock;

class AdminManager
{
    /**
     * @OnStart()
     */
    public function onStart() {
        if (!LightCache::isset("admin_list")) { //一次性代码，首次执行才会执行if
            LightCache::set("admin_list", [ // 框架启动时初始化管理员列表
                "123456",
                "234567"
            ], -2); // 这里用 -2 的原因是将这一列表持久化保存，避免关闭框架后丢失
        }
    }

    /**
     * @CQCommand(match="添加管理员")
     * @Middleware("admin")
     */
    public function addAdmin() { //只有管理员才能添加管理员
        $qq = ctx()->getNextArg("请输入要添加管理员的QQ(qq号码，不可at）");
        SpinLock::lock("admin_list");        //如果是多进程模式的话需要加锁
        $ls = LightCache::get("admin_list");
        if (!in_array($qq, $ls)) $ls[] = $qq;
        LightCache::set("admin_list", $ls, -2);
        SpinLock::unlock("admin_list");      //如果是多进程模式的话需要加锁
        return "成功添加 $qq 到管理员列表！";
    }
}
```

<chat-box :my-chats="[
    {type:2,content:'现在我是 123456'},
    {type:0,content:'禁言 13579 60'},
    {type:1,content:'禁言成功！'},
    {type:0,content:'解除禁言 13579'},
    {type:1,content:'解除禁言成功！'},
    {type:0,content:'添加管理员 98765'},
    {type:1,content:'成功添加 98765 到管理员列表！'},
    {type:2,content:'现在我是98765'},
    {type:0,content:'禁言 13579'},
    {type:1,content:'请输入禁言的时间（秒）'},
    {type:0,content:'120'},
    {type:1,content:'禁言成功！'},
]"></chat-box>
