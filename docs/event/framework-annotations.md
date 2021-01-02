# 框架核心注解事件

框架核心注解事件区别于机器人和路由注解事件，这里框架注解事件都是**直接**或封装调用 Swoole 的回调事件的，所以对一些比较底层或者基础的操作都在这里做，例如收到 HTTP 或 WebSocket 连接后执行的事件函数。

## OnSwooleEvent()

绑定 Swoole 所相关的事件，例如 WebSocket 接入、收到 WS 消息、关闭 WS 连接，HTTP 请求到达等。这个是旧的统一的 Swoole 事件分发注解。请尽量使用上面几个新的注解。

### 属性

| 类型         | 值                                         |
| ------------ | ------------------------------------------ |
| 名称         | `@OnSwooleEvent`                           |
| 触发前提     | 当参数指定的 `type` 对应的事件被触发后激活 |
| 命名空间     | `ZM\Annotation\Swoole\OnSwooleEvent`       |
| 适用位置     | 方法                                       |
| 返回值处理   | 无                                         |
| 注解绑定参数 |                                            |

### 注解参数

| 参数名称 | 参数范围                                                 | 用途                                            | 默认             |
| -------- | -------------------------------------------------------- | ----------------------------------------------- | ---------------- |
| type     | `string`，支持填入 `open`，`request`，`close`，`message` | 限定事件的类型，**必填**                        |                  |
| rule     | `string`，必须是可执行且返回 bool 的 PHP 代码            | 例如判断连接是否为 QQ 机器人（`connectIsQQ()`） | 空，rule 为 true |
| level    | `int`                                                    | 事件优先级（越大越靠前）                        | 20               |

### 事件绑定参数

`$conn`: [ConnectionObject](/advanced/inside-class/) 类型，返回一个当前 WS 连接的连接对象。

### 示例1（机器人连接框架后输出信息）

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
class Hello {
    /**
 	 * 在机器人客户端连接框架后向终端输出信息
 	 * @OnSwooleEvent("open",rule="connectIsQQ()")
	 * @param $conn
 	 */
	public function onConnect(ConnectionObject $conn) {
	    Console::info("机器人 " . $conn->getOption("connect_id") . " 已连接！");
	}
}
```

这里的 Console 是终端输出组件，详情见组件一栏对应的文档查询。

### 示例2（阻断 Chrome 访问框架时多访问一次的问题）

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Event\EventDispatcher;
class Hello {
    /**
     * 阻止 Chrome 自动请求 /favicon.ico 导致的多条请求并发和干扰
     * @OnSwooleEvent("request",rule="ctx()->getRequest()->server['request_uri'] == '/favicon.ico'",level=200)
     */
    public function onRequest() {
        EventDispatcher::interrupt();
    }
}
```

其中 EventDispatcher 为事件分发器，interrupt 是通用阻断方法，如果你平常只使用阻断，则只需掌握这一个方法即可，`EventDispatcher::interrupt()` 在所有事件内可用。