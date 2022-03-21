# 全局方法

全局方法就是 PHP 的全局函数，任意位置都可以调用，无需使用 use 字样。

## getClassPath()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L24)

根据加载的用户编写的代码类名来获取类所在的文件路径。

**src/Module/Example/Hello.php**

```php
<?php
namespace Module\Example;
class Hello {  ...  }
```

**src/Module/Example/Start.php**

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnStart;
class Start {
    /**
     * @OnStart()
     */
    public function onStart() {
        Console::info("Path: ".getClassPath(Hello::class));
    }
}
```

**输出结果**

```
[11:12:02] [I] [#0] Path: /mnt/d/project/zhamao-framework/src/Module/Example/Hello.ph
```

## explodeMsg()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L39)

切割字符串的函数，支持多空格，换行，tab。

定义：`explodeMsg($msg, $ban_comma = false)`

```php
$s = explodeMsg("你好啊 你好你好\n我还有多个空格      哈哈哈");
echo json_encode($s, 128|256); // ["你好啊","你好你好","我还有多个空格","哈哈哈"]
```

## unicode_decode()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L54)

Unicode 解码，一般用于被转义的 Unicode 转回来。

```php
echo unicode_decode("\u4f60\u597d"); // 你好
```

## matchPattern()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L91)

根据星号匹配字符串（非正则表达式）。

匹配示例：

- `你今天*了吗` -> 你今天喝水了吗
- `*的天气怎么样` -> 德州的天气怎么样
- `把*翻译成*` -> 把茶翻译成英语

定义：`matchPattern($pattern, $context)`

`$pattern` 为匹配模式，例如 `你今天*了吗`。

`$context` 为要判断是否匹配的内容。

返回值：`bool`，当为 true 时代表规则是匹配的，false 代表不匹配。

```php
matchPattern("*是个啥？", "996是个啥？"); // true
matchPattern("我想听*唱歌", "你想听谁唱歌"); // false
matchPattern("*把*翻译成*", "请把你好翻译成阿拉伯语"); // true
```

## split_explode()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L103)

和 `explodeMsg()` 类似，用作分割字符串，不过此函数加入了对 `中文|数字` 两者的分割，也就是说中文和数字之间也会被分割。

定义：`split_explode($del, $str, $divide_en = false)`

```php
split_explode(" ", "前进20 急啊急啊"); // ["前进","20","急啊急啊"]
```

`$del` 和 `explode()` 的第一个参数作用相同，作为初期分割的标志。

`$str` 表示待分割的内容。

`$divide_en` 表示是否分割中文和英文，如果为是，则中文和英文之间也会被分割开。

## matchArgs()

[源码](https://github.com/zhamao-robot/zhamao-framework/blob/master/src/ZM/global_functions.php#L135)

`matchPattern()` 的扩展，如果 `matchPattern()` 格式的字符串和模式匹配成功，则通过星号位置来提取星号匹配到的内容，参数同 `matchPattern()`。

```php
$r = matchArgs("把*翻译成*", "把日语翻译成英语"); // ["日语","英语"]
```

## connectIsQQ()

判断当前 WebSocket 连接是否为 OneBot 标准的机器人客户端。

## connectIsDefault()

判断连接是否是未定义类型的 WebSocket 连接。

## connectIs()

判断连接是否是对应类型的 WebSocket 连接。

```php
connectIs("your_another_type_connect");
```

## set_coroutine_params()

设置当前上下文中的一些变量。

```php
set_coroutine_params(["data" => [
    "post_type" => "message",
    ...
]]);
```

## ctx()

别名：`context()`，获取当前协程的上下文，见 [上下文](/component/context/)。

## zm_sleep()

协程版 `sleep()` 函数。

定义：`zm_sleep($s = 1)`

`$s`：睡眠的时间：秒，可支持小数。（例如：0.001 代表 1 毫秒）

为什么不用 PHP 自带的 sleep 呢？因为炸毛框架是基于协程的，协程版 sleep 需要使用 Swoole 自带的 sleep。此函数做了一个简单的封装。

```php
zm_sleep(5);
zm_sleep(0.05);
```

## zm_exec()

执行系统命令，替代 PHP 的 `exec()`。

定义：`zm_exec($cmd)`

返回值：

```php
array(
    'code'   => 0,  // 进程退出的状态码
    'signal' => 0,  // 信号
    'output' => 'hello world', // 输出内容
);
```

```php
$result = zm_exec("echo 'hello world'")["output"];
```

## zm_cid()

获取当前协程的 ID，效果等同于 `\Swoole\Coroutine::getCid()`。

## zm_yield()

挂起当前协程，直到手动恢复，效果等同于 `\Swoole\Coroutine::yield()`。

## zm_resume()

恢复继续执行协程，效果等同于 `\Swoole\Coroutine::resume()`。

```php
$r = 0;
function test() {
    echo "hello-1\n";
    global $r;
    $r = zm_cid();
    zm_yield();
    echo "hello-2\n";
}

go("test");
echo "hello-3\n";
zm_resume($r);
```

输出结果：

```
hello-1
hello-3
hello-2
```

## server()

获取 Swoole Server 对象进行操作，效果等同于 `\ZM\Framework::$server`。

```php
echo server()->worker_id.PHP_EOL; // 0
```

## bot()

返回 ZMRobot 操作机器人 API 的对象。

对于默认的模式，如果框架连接了多个机器人实例，则会随机返回一个机器人的 API 实例。如果使用了单例模式，则返回单例模式的机器人 API 实例。

```php
bot()->sendPrivateMsg(123456, "你好啊！！");
// 等同于 ZMRobot::getRandom()->sendPrivateMsg(123456, "你好啊！！");
```

## zm_atomic()

获取计时器，效果同 `\ZM\Store\ZMAtomic::get($name)`。

定义：`zm_atmoic($name)`

## uuidgen()

> 2.2.5 版本起可用。

生成一个随机的 uuid，支持大写或小写。

定义：`uuidgen($uppercase = false)`

当 `$uppercase` 为 `true` 时，返回的 uuid 中字母都是大写。

## working_dir()

> 2.2.6 版本起可用。

获取框架运行的工作目录。例如你是从 `/root/framework-starter/` 目录启动的框架，`vendor/bin/start server`，那么 `working_dir()` 返回的就是 `/root/framework-starter`。（注意，返回的目录最后没有斜杠，请自行添加。）

## getAllFdByConnectType()

获取同类型的所有连接的描述符 ID。

定义：`getAllFdByConnectType(string $type = 'default'): array`

当 `$type` 为 `qq` 时，则返回所有 OneBot 机器人接入的 WebSocket 连接号。

## zm_dump()

更漂亮地输出变量值，可替代 `var_dump()`。

```php
class Pass {
    public $foo = 123;
    public $bar = ["a", "b"];
}
$pass = new Pass();
$pass->obj = true;
zm_dump($pass);
```

![](https://static.zhamao.me/images/docs/ba026ca11332b1a4ad68a549165230e6.png)

## zm_config()

> v2.4.0 起可用。

同 `ZMConfig::get()`。

定义：`zm_config($name, $key = null)`。

有关 ZMConfig 模块的说明，见 [指南 - 基本配置](/guide/basic-config)。

```php
zm_config("global"); //等同于 ZMConfig::get("global");
zm_config("global", "swoole"); //等同于 ZMConfig::get("global", "swoole");
```

## zm_info()

> v2.4.0 起可用。（下面的 log 类也一样）

同 `Console::info($msg)`。

## zm_debug()

同 `Console::debug($msg)`。

## zm_warning()

同 `Console::warning($msg)`。

## zm_success()

同 `Console::success($msg)`。

## zm_error()

同 `Console::error($msg)`。

## zm_verbose()

同 `Console::verbose($msg)`。
