# 事件跟踪器及调试

众所周知，炸毛框架中的事件由内置的事件分发器（EventDispatcher）负责分发，但调试事件分发在之前的版本比较困难，例如不能获取到事件如何被调用，以及事件如何被捕获。

EventTracer 的作用是记录事件的调用顺序，以便于调试。

命名空间使用指南：`use ZM\Event\EventTracer;`

## EventTracer::getCurrentEvent() - 获取当前注解事件对象

```php
/**
 * @OnStart()
 */
public function onStart() {
    zm_dump(EventTracer::getCurrentEvent());
}
/*
^ ZM\Annotation\Swoole\OnStart^ {#192
  +worker_id: 0
  +method: "onStart"
  +class: "Module\Example\Hello"
}
*/
```

这里这个方法必须在注解事件内执行，如果在注解事件外执行，将会返回 `null`。

## EventTracer::getCurrentEventMiddlewares() - 获取当前注解事件的中间件们

```php
/**
 * @OnStart()
 * @Middleware("timer")
 */
public function onStart() {
    zm_dump(EventTracer::getCurrentEventMiddlewares());
}
/*
^ array:1 [
  0 => ZM\Annotation\Http\Middleware^ {#194
    +middleware: "timer"
    +params: []
    +method: "onStart"
    +class: "Module\Example\Hello"
  }
]
*/
```

返回值为当前注解事件的中间件们，如果没有注解中间件，返回 `[]`。

## EventTracer::getEventTraceList() - 获取注解事件的列表

此处返回的是 `getCurrentEvent()` 相同的对象，但是返回的是一个数组，数组中的元素是注解事件。

```php
/**
 * 一个简单随机数的功能demo
 * 问法1：随机数 1 20
 * 问法2：从1到20的随机数
 * @CQCommand("随机数")
 * @Middleware("timer")
 * @CQCommand(pattern="*从*到*的随机数")
 * @return string
 */
public function randNum() {
    // 此处为随机数代码
    zm_dump(EventTracer::getEventTraceList());
    return "随机数：" . rand(1, 20);
}

/*
^ array:2 [
  0 => ZM\Annotation\CQ\CQCommand^ {#193
    +match: ""
    +pattern: "*从*到*的随机数"
    +regex: ""
    +start_with: ""
    +end_with: ""
    +keyword: ""
    +alias: []
    +message_type: ""
    +user_id: 0
    +group_id: 0
    +discuss_id: 0
    +level: 20
    +method: "randNum"
    +class: "Module\Example\Hello"
  }
  1 => ZM\Annotation\Swoole\OnMessageEvent^ {#165
    +connect_type: "default"
    +rule: "connectIsQQ()"
    +level: 99
    +method: "handleByEvent"
    +class: "ZM\Module\QQBot"
  }
]
*/
```

## EventDispatcher::enableEventTrace() - 启用事件跟踪器

还没写完，不着急。
