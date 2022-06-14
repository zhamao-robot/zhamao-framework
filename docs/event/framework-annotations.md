# 框架核心注解事件

框架核心注解事件区别于机器人和路由注解事件，这里框架注解事件都是**直接**或封装调用 Swoole 的回调事件的，所以对一些比较底层或者基础的操作都在这里做，例如收到 HTTP 或 WebSocket 连接后执行的事件函数。

## OnOpenEvent()

当有 WebSocket 连接接入框架时，触发注解事件。

### 属性

| 类型       | 值                                          |
| ---------- | ------------------------------------------- |
| 名称       | `@OnOpenEvent`                              |
| 触发前提   | 当有 WebSocket 连接接入框架时，触发注解事件 |
| 命名空间   | `ZM\Annotation\Swoole\OnOpenEvent`          |
| 适用位置   | 方法                                        |
| 返回值处理 | 无                                          |

### 参数

| 参数名称     | 参数范围 | 用途                                                         | 默认 |
| ------------ | -------- | ------------------------------------------------------------ | ---- |
| connect_type | `string` | 限定连接的类型，通过炸毛框架支持的方式指定传入类型，详见 [进阶 - 接入 WebSocket 客户端](/advanced/connect-ws-client) |      |

### 用法

```java
@OnOpenEvent("foo")
@OnOpenEvent(connect_type="default")
```

### 事件绑定参数

`$conn`: [ConnectionObject](/advanced/connect-ws-client/) 类型，返回一个当前 WS 连接的连接对象。

## OnCloseEvent()

当有 WebSocket 连接断开框架时，触发注解事件。

### 属性

| 类型       | 值                                          |
| ---------- | ------------------------------------------- |
| 名称       | `@OnCloseEvent`                             |
| 触发前提   | 当有 WebSocket 连接断开框架时，触发注解事件 |
| 命名空间   | `ZM\Annotation\Swoole\OnCloseEvent`         |
| 适用位置   | 方法                                        |
| 返回值处理 | 无                                          |

### 参数

| 参数名称     | 参数范围 | 用途                                                         | 默认 |
| ------------ | -------- | ------------------------------------------------------------ | ---- |
| connect_type | `string` | 限定连接的类型，通过炸毛框架支持的方式指定传入类型，详见 [进阶 - 接入 WebSocket 客户端](/advanced/connect-ws-client) |      |

### 用法

```java
@OnCloseEvent("foo")
@OnCloseEvent(connect_type="default")
```

### 事件绑定参数

`$conn`: [ConnectionObject](/advanced/connect-ws-client/) 类型，返回一个当前 WS 连接的连接对象。

## OnRequestEvent()

当 HTTP 请求接入时，触发注解事件。

### 属性

| 类型       | 值                                    |
| ---------- | ------------------------------------- |
| 名称       | `@OnRequestEvent`                     |
| 触发前提   | 当 HTTP 请求接入时，触发注解事件      |
| 命名空间   | `ZM\Annotation\Swoole\OnRequestEvent` |
| 适用位置   | 方法                                  |
| 返回值处理 | 无                                    |

### 参数

| 参数名称 | 参数范围                                      | 用途                     | 默认             |
| -------- | --------------------------------------------- | ------------------------ | ---------------- |
| rule     | `string`，必须是可执行且返回 bool 的 PHP 代码 | 前置条件                 | 空，rule 为 true |
| level    | `int`                                         | 事件优先级（越大越靠前） | 20               |

## OnMessageEvent()

当有 WebSocket 连接接入框架后发送过来消息，触发注解事件。

### 属性

| 类型       | 值                                                      |
| ---------- | ------------------------------------------------------- |
| 名称       | `@OnMessageEvent`                                       |
| 触发前提   | 当有 WebSocket 连接接入框架后发送过来消息，触发注解事件 |
| 命名空间   | `ZM\Annotation\Swoole\OnMessageEvent`                   |
| 适用位置   | 方法                                                    |
| 返回值处理 | 无                                                      |

### 参数

| 参数名称     | 参数范围 | 用途                                                         | 默认 |
| ------------ | -------- | ------------------------------------------------------------ | ---- |
| connect_type | `string` | 限定连接的类型，通过炸毛框架支持的方式指定传入类型，详见 [进阶 - 接入 WebSocket 客户端](/advanced/connect-ws-client) |      |

### 用法

```java
@OnMessageEvent("foo")
@OnMessageEvent(connect_type="default")
```

### 事件绑定参数

`$conn`: [ConnectionObject](/advanced/connect-ws-client/) 类型，返回一个当前 WS 连接的连接对象。

## OnPipeMessageEvent()

当有 其他 Worker 进程通信发来指令，激活响应。（2.2.0 版本可用）

### 属性

| 类型       | 值                                                      |
| ---------- | ------------------------------------------------------- |
| 名称       | `@OnPipeMessageEvent`                                   |
| 触发前提   | 当有 WebSocket 连接接入框架后发送过来消息，触发注解事件 |
| 命名空间   | `ZM\Annotation\Swoole\OnPipeMessageEvent`               |
| 适用位置   | 方法                                                    |
| 返回值处理 | 无                                                      |

### 参数

| 参数名称 | 参数范围 | 用途         | 默认 |
| -------- | -------- | ------------ | ---- |
| action   | `string` | 限定动作名称 |      |

### 用法

```java
@OnPipeMessageEvent("foo")
@OnPipeMessageEvent(action="bar")
```

### 事件绑定参数

`$data`: 数组，内容如下：

```php
[
    "action" => "你的上面的名称",
    ... //其他自己发送时随便定义，带什么都行
]
```

## OnSwooleEvent()

绑定 Swoole 所相关的事件，例如 WebSocket 接入、收到 WS 消息、关闭 WS 连接，HTTP 请求到达等。这个是旧的统一的 Swoole 事件分发注解。**请尽量使用上面几个新的注解**。

### 属性

| 类型       | 值                                         |
| ---------- | ------------------------------------------ |
| 名称       | `@OnSwooleEvent`                           |
| 触发前提   | 当参数指定的 `type` 对应的事件被触发后激活 |
| 命名空间   | `ZM\Annotation\Swoole\OnSwooleEvent`       |
| 适用位置   | 方法                                       |
| 返回值处理 | 无                                         |

### 注解参数

| 参数名称 | 参数范围                                                 | 用途                                            | 默认             |
| -------- | -------------------------------------------------------- | ----------------------------------------------- | ---------------- |
| type     | `string`，支持填入 `open`，`request`，`close`，`message` | 限定事件的类型，**必填**                        |                  |
| rule     | `string`，必须是可执行且返回 bool 的 PHP 代码            | 例如判断连接是否为 QQ 机器人（`connectIsQQ()`） | 空，rule 为 true |
| level    | `int`                                                    | 事件优先级（越大越靠前）                        | 20               |

### 事件绑定参数

`$conn`: [ConnectionObject](/advanced/connect-ws-client/) 类型，返回一个当前 WS 连接的连接对象。

## OnStart()

在框架加载后执行的注解事件，用于初始化 Worker 进程，此注解事件会在 Worker 进程中执行，且可以指定在哪个 Worker 进程中执行。

### 属性

| 类型       | 值                             |
| ---------- | ------------------------------ |
| 名称       | `@OnStart`                     |
| 触发前提   | 在框架加载后激活               |
| 命名空间   | `ZM\Annotation\Swoole\OnStart` |
| 适用位置   | 方法                           |
| 返回值处理 | 无                             |

### 注解参数

| 参数名称  | 参数范围                                                     | 用途                     | 默认 |
| --------- | ------------------------------------------------------------ | ------------------------ | ---- |
| worker_id | `int`，要在哪个 Worker 进程上执行，默认为 0，范围是 0～{你设定的 Worker 数量-1}，如果是 -1 的话，则会在所有 Worker 进程上触发。 | 限定只执行的 Worker 进程 |      |

## OnTick()

在框架加载后创建毫秒计时器。

### 属性

| 类型       | 值                            |
| ---------- | ----------------------------- |
| 名称       | `@OnTick`                     |
| 触发前提   | 在框架加载后激活              |
| 命名空间   | `ZM\Annotation\Swoole\OnTick` |
| 适用位置   | 方法                          |
| 返回值处理 | 无                            |

### 注解参数

| 参数名称  | 参数范围                                                     | 用途                     | 默认 |
| --------- | ------------------------------------------------------------ | ------------------------ | ---- |
| tick_ms   | `int`，**必填**，间隔的毫秒数，例如 1 秒间隔为 `1000`，范围大于 0，小于 86400000。 |                          |      |
| worker_id | `int`，要在哪个 Worker 进程上执行，默认为 0，范围是 0～{你设定的 Worker 数量-1}，如果是 -1 的话，则会在所有 Worker 进程上触发。 | 限定只执行的 Worker 进程 |      |

## OnTask()

定义一个在工作进程中运行的任务函数。详情见 [进阶 - 使用 TaskWorker 进程处理密集运算](/advanced/task-worker)。

### 属性

| 类型       | 值                            |
| ---------- | ----------------------------- |
| 名称       | `@OnTask`                     |
| 触发前提   | 在框架加载后激活              |
| 命名空间   | `ZM\Annotation\Swoole\OnTask` |
| 适用位置   | 方法                          |
| 返回值处理 | 有，返回 Worker 进程的结果    |

### 注解参数

| 参数名称  | 参数范围                                                     | 用途         | 默认 |
| --------- | ------------------------------------------------------------ | ------------ | ---- |
| task_name | `string`，**必填**，任务函数的名称，不建议重复。             |              |      |
| rule      | 设置触发前提，PHP 代码，返回 bool 值即可，参考 OnRequestEvent | 限定是否执行 | 空   |

## OnSetup()

在框架加载前执行的代码。此部分代码是在主进程执行的，不可在此事件中使用任何协程相关的功能。

比如我们要改变所有进程的 ini 设置，这时使用 `@OnStart(-1)` 这样只设置了 Worker 进程的内容，而主进程和管理进程无法被覆盖到。如果需要设置全局的一些配置，务必在此 `@OnSetup` 注解下执行。

### 属性

| 类型       | 值                             |
| ---------- | ------------------------------ |
| 名称       | `@OnSetup`                     |
| 触发前提   | 在框架加载前激活               |
| 命名空间   | `ZM\Annotation\Swoole\OnSetup` |
| 适用位置   | 方法                           |
| 返回值处理 | 无                             |

### 注解参数

无。

## OnSave()

框架退出和每 15 分钟在 Worker #0 执行的代码。建议这里用来编写内存数据持久化的代码，如将 WorkerCache、内存全局变量存到文件。

| 类型       | 值                             |
| ---------- | ------------------------------ |
| 名称       | `@OnSetup`                     |
| 触发前提   | 每 15 分钟或 Ctrl+C、`server:stop` 等方式退出框架时               |
| 命名空间   | `ZM\Annotation\Swoole\OnSave` |
| 适用位置   | 方法                           |
| 返回值处理 | 无                             |

### 注解参数

无。

## TerminalCommand()

添加一个远程终端的自定义命令。（2.4.0 版本起可用）

### 属性

| 类型       | 值                                      |
| ---------- | --------------------------------------- |
| 名称       | `@TerminalCommand`                      |
| 触发前提   | 连接到远程终端可触发                    |
| 命名空间   | `ZM\Annotation\Command\TerminalCommand` |
| 适用位置   | 方法                                    |
| 返回值处理 | 无                                      |

### 注解参数

| 参数名称    | 参数范围                       | 默认 |
| ----------- | ------------------------------ | ---- |
| command     | `string`，**必填**，命令字符串 |      |
| alias       | `string`，可选，命令的别名     |      |
| description | `string`，要显示的帮助文本     | 空   |

## 示例1（机器人连接框架后输出信息）

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
class Hello {
    /**
 	 * 在机器人客户端连接框架后向终端输出信息
 	 * @OnOpenEvent("qq")
	 * @param $conn
 	 */
	public function onConnect(ConnectionObject $conn) {
	    Console::info("机器人 " . $conn->getOption("connect_id") . " 已连接！");
	}
}
```

这里的 Console 是终端输出组件，详情见组件一栏对应的文档查询。

## 示例2（阻断 Chrome 访问框架时多访问一次的问题）

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Event\EventDispatcher;
class Hello {
    /**
     * 阻止 Chrome 自动请求 /favicon.ico 导致的多条请求并发和干扰
     * @OnRequestEvent(rule="ctx()->getRequest()->server['request_uri'] == '/favicon.ico'",level=200)
     */
    public function onRequest() {
        EventDispatcher::interrupt();
    }
}
```

其中 EventDispatcher 为事件分发器，interrupt 是通用阻断方法，如果你平常只使用阻断，则只需掌握这一个方法即可，`EventDispatcher::interrupt()` 在所有事件内可用。

## 示例3（接收 WS 客户端发来的数据）

见 [接入 WebSocket 客户端](/advanced/connect-ws-client)。

## 示例4（使用 OnStart 给所有 Worker 进程写入缓存提速）

如果你有一些数据存到了文件、数据库中，且是只读不写的，那么就可以使用此方法将这个文件或者数据库的内容读入 Worker 进程的内存中进行使用来提速。

假设我们有一个大文件 json，里面存着一份题库，例如：

```json
{
    "0": {
        "question": "法的调整对象是（ ）。",
        "answer": {
            "A": "行为关系",
            "B": "思想关系",
            "C": "利益关系",
            "D": "各种社会资源"
        },
        "key": "A",
        "answer_type": 0
    },
    "1": {
        "question": "法律与其他社会规范的区别在于（ ）。",
        "answer": {
            "A": "是调整人们行为的规范",
            "B": "有约束力",
            "C": "由国家强制力保证执行",
            "D": "规定制裁措施"
        },
        "key": "C",
        "answer_type": 0
    }
}
```

那么我们可以使用 OnStart 来实现一个，将此文件读取到每个 Worker 进程中，并且快速取用的功能（以下做了一个简单的查题功能）：

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnStart;
use ZM\Annotation\CQ\CQCommand;
use ZM\Console\Console;
class Hello {
    public static $tiku = [];
    /**
     * @OnStart(-1)
     */
    public function onStart() { // 注意，此函数将会在每个 Worker 执行一次
        $file = file_get_contents("tiku.json"); //从文件读取json
        $json = json_decode($file, true); //json解析
        Hello::$tiku = $json; //将解析后的数组以静态变量的方式存到每个 Worker 的内存中
        Console::success("加载题库完成！");
    }
    /**
     * @CQCommand("找题")
     */
    public function findQuestion() {
        $tiku_id = ctx()->getNumArg("请输入题目的序号");
        if(!isset(Hello::$tiku[$tiku_id])) return "题目id为".$tiku_id."的题目不存在！";
        $timu = Hello::$tiku[$tiku_id];
        $msg = "题目名称：".$timu["question"];
        foreach($timu["answer"] as $k => $v) {
            $msg .= "\n".$k.". ".$v;
        }
        $msg .= "\n正确答案：".$timu["key"];
        return $msg;
    }
}
```

终端效果：（我们假设运行框架的电脑是四核 CPU）

```log
[14:28:00] [S] [#0] 加载题库完成！
[14:28:00] [S] [#2] 加载题库完成！
[14:28:00] [S] [#1] 加载题库完成！
[14:28:00] [S] [#3] 加载题库完成！
```

聊天效果：

<chat-box :my-chats="[
    {type:0,content:'找题 1'},
    {type:1,content:'题目名称：法律与其他社会规范的区别在于（ ）。\nA. 是调整人们行为的规范\nB. 有约束力\nC. 由国家强制力保证执行\nD. 规定制裁措施\n正确答案：C'},
]"></chat-box>

## 示例5（创建每分钟自动执行的爬虫）

```php
/**
 * @OnTick(tick_ms=60000,worker_id=0)
 */
public function onCrawl() {
    $data = Foo::bar(); //这里是你自己写的要爬的接口等等一系列操作
    LightCache::set("your_data_key_name", $data); //将爬虫数据存入 LightCache 轻量缓存
}
```

## 示例6（创建一个远程终端命令并调试框架）

> 开个坑，以后填。（__填坑标记__）
> 
