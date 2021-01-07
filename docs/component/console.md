# Console 控制台

Console 类所在命名空间：`\ZM\Console\Console`

Console 类为框架的终端输出管理类。

## 设置 Log 输出等级

**输出等级** 控制了输出到命令行的内容的重要性。在框架的输出中，消息有以下几种不同等级的类别

- **error** / **log**: 0
- **warning**: 1
- **info** / **success**: 2
- **verbose**: 3
- **debug**: 4

输出等级设置后显示的消息类别为小于等于当前 log 的。假设你将 log 等级设置为 3，你可以看到除 debug 外的所有 log 内容。

通过配置文件 `global.php` 中的 `init_atomics -> info_level` 的数值你可以更改框架的默认 log 等级（默认为 2）。

你也可以在启动框架的命令行中添加参数来切换 log 等级：

```bash
vendor/bin/start server --log-error # 以 error 等级启动框架
vendor/bin/start server --log-warning # 以 warning 等级启动框架
vendor/bin/start server --log-info # 以 info 等级启动框架
vendor/bin/start server --log-verbose # 以 verbose 等级启动框架
vendor/bin/start server --log-debug # 以 debug 等级 启动框架
```

## 使用 Log 输出内容

作为模块开发者的你，你可以主动调用框架内的 Console 类输出信息到终端。

### Console::log()

输出 0 级别的普通 log。

- 参数：`$msg, $color`，分别为内容和字体颜色。

> 此 log 不会被 info_level 所限制，无论如何也会输出到终端。

### Console::error()

输出 error 级别的红色醒目 log。一般此 log 为框架内部出现不可忍受的错误，比如内存不足、PHP fatal error 等错误。

- 参数：`$msg`

> 此 log 不会被 info_level 所限制，无论如何也会输出到终端。

### Console::warning()

输出 warning 级别的 log。

!!! warning 注意

	框架内出现的用户态异常，比如无法发送 API、无法连接数据库等错误，都是 warning 错误，不会导致框架崩溃或功能错误的异常情况建议都使用 warning 输出而不是 error。


### Console::info()

输出 info 级别的 log。

### Console::success()

输出 success 级别的log。

### Console::verbose()

输出 verbose 级别的 log。

### Console::debug()

输出 debug 级别的 log。

### Console::stackTrace()

输出栈追踪信息。

### Console::setColor()

返回：彩色的字符串。

- **string**: 要变颜色的字符串
- **color**: 要变的颜色。支持 `red`，`green`，`yellow`，`reset`，`blue`，`gray`，`gold`，`pink`，`lightblue`，`lightlightblue`

```php
Console::log("This is normal msg. (0)");
Console::error("This is error msg. (0)");
Console::warning("This is warning msg. (1)");
Console::info("This is info msg. (2)");
Console::success("This is success msg. (2)");
Console::verbose("This is verbose msg. (3)");
Console::debug("This is debug msg. (4)");
Console::stackTrace();
$str = Console::setColor("I am gold color.", "gold");
```

## 终端交互命令

炸毛框架支持从终端输入命令来进行一些操作，例如重启框架、停止框架、执行函数等。

::: warning 注意

在 Docker、systemd、daemon 状态下启动的框架会自动关闭终端等待输入，交互不可用。

:::

### reload

重新加载除 `src/Framework/` 下的所有模块。

- 别名：`r`

### stop

停止框架。

### logtest

输出各种等级的 log 示例文本。

### call

执行对应类的成员方法。下面是例子：

```bash
call \ZM\Utils\ZMUtil reload
```

### bc

直接执行 PHP 代码，输入格式为 base64。

```bash
bc XEZyYW1ld29ya1xDb25zb2xlOjp3YXJuaW5nKCJoZWxsbyB3YXJuaW5nISIpOw==
# 代码内容：\ZM\Console\Console::warning("hello warning!");
# 终端输出：[19:14:32] [W] hello warning!
```

### echo

输出文本

```bash
echo hello
```

### color

按照颜色输出文本

```bash
color green 我是绿色的字
```

## MOTD

在 1.4 版本开始，框架支持启动时的 motd 内容修改。

文件位置：`config/motd.txt`