# 中间件注解

对于 `@RequestMapping` 等注解绑定的事件函数，还支持中间件，可以完成 Session 会话、认证、日志记录等功能。中间件是用于控制 `请求到达` 和 `响应请求` 的整个流程的。从一定意义上来说相当于切面编程（AOP）。

在炸毛框架中，中间件最直白的意思就是注解事件执行前、执行后、执行过程中可进行插入代码但不破坏原有代码。

```伪代码
@中间件1
@带条件的注解1
function 我的方法() {
 blablabla...
}
//插入中间件，下面是执行流程
-> 判断注解1的执行条件是否为true
-> 中间件1的前置插入代码
-> 我的方法
-> 中间件1的后置插入代码
X -> 我的方法有异常时执行中间件1的异常处理

//不插入中间件，下面是执行流程
-> 判断注解1的执行条件是否为true
-> 我的方法
X -> 有异常则直接跳到最外层被框架捕获
```

中间件和事件分发器是紧密相连的，炸毛框架的内部分发器在分发注解事件的过程中会判断将要执行的事件是否含有中间件，框架内部执行流程图见下一章：事件分发器。

## 定义中间件

下方就是一个可以在终端打印路由函数运行的总时间的中间件，只需给中间件标明里面的 `@MiddlewareClass` 到中间件的类上就可以了。

```php
<?php

namespace Module\Middleware;

use Exception;
use ZM\Annotation\Http\HandleAfter;
use ZM\Annotation\Http\HandleBefore;
use ZM\Annotation\Http\HandleException;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Console\Console;
use ZM\Http\MiddlewareInterface;

/**
 * @MiddlewareClass("timer")
 */
class TimerMiddleware implements MiddlewareInterface
{
    private $starttime;

    /**
     * @HandleBefore()
     * @return bool
     */
    public function onBefore() {
        $this->starttime = microtime(true);
        return true;
    }

    /**
     * @HandleAfter()
     */
    public function onAfter() {
        Console::info("Using " . round((microtime(true) - $this->starttime) * 1000, 2) . " ms.");
    }

    /**
     * @HandleException(\Exception::class)
     * @param Exception $e
     * @throws Exception
     */
    public function onException(Exception $e) {
        Console::error("Using " . round((microtime(true) - $this->starttime) * 1000, 2) . " ms but an Exception occurred.");
        throw $e;
    }
}

```

技术要素：

1. 将需要声明为中间件的 class 类标上注解 `@MiddlewareClass`，并带有参数，参数为中间件名称，字符串即可。
2. 使用 `@MiddlewareClass` 的需要先 use：`use ZM\Annotation\Http\MiddlewareClass;`。
3. 类成员中声明执行前插入、执行后插入和异常捕获函数也需要注解，分别是 `@HandleBefore`，`@HandleAfter`，`@HandleException`，都在 `ZM\Annotation\Http` 命名空间下。
4. `@HandleBefore` 类似 `@CQBefore`，需要返回 bool 类型值，如果不返回，默认为 true。当为 true 时，则不会阻断执行事件函数本身。
5. 中间件内的函数不可被绑定为注解事件。
6. `@HandleException` 可以写多个，但其中的参数只能写想要捕获的异常的类全称，例如 `\Exception::class` 返回的就是 `\\Exception`，`\ZM\Exception\InterruptException::class` 返回的是 `ZM\\Exception\\InterruptException`，举的这两个例子这样写都是可以的。
7. 如果 `@HandleException` 有多个的话，则会按照声明顺序依次让其捕获，看其是否为要被捕获的错误的类或父类。例如在最后一个 `@HandleException` 捕获 `\Throwable` 则最终此中间件会捕获所有异常。
8. 中间件内可以正常使用和注解事件执行的内容同一上下文，例如 `@RequestMapping` 下你可以使用 `ctx()->getRequest()`，`@CQMessage` 可以使用 `ctx()->getMessage()` 等，以此类推。

## 使用中间件

如上图，我们举了一个非常简单的例子，打印出函数执行的时间。我们假设一个需要耗时较长的函数：

```php
/**
 * @RequestMapping("/testTime")
 * @Middleware("timer")
 */
public function testTime() {
    zm_sleep(3); //等待3秒再返回
    return "OK!";
}
```

在执行后，你的执行结果可能为：

```
[11:18:56] [I] [#0] Using 3000.07 ms
```

或者，我们也可以将中间件注解写到类上：

```php
/**
 * @Middleware("timer")
 */
class Hello {
  /**
   * @RequestMapping("/test/ping")
   */
  public function ping(){
    return "pong";
  }
  /**
   * @RequestMapping("/test/ping2")
   */
  public function ping2(){
    return "pong2";
  }
}
```

效果等同于给此类下每个注解事件写一个 `@Middleware`。

## 使用多个中间件

多个使用中间件可以同时生效多个流程的中间件。这里要注意，多个中间件中，`@HandleBefore` 方法中如果返回了 `false`，则不会执行接下来的中间件和事件本身要触发的函数，直接跳到最后此中间件的 `@HandleAfter` 方法。

```php
/**
 * @CQCommand("你好")
 * @Middleware("timer1")
 * @Middleware("timer2")
 */
public function hello() { return "成功执行！"; }
```

## 使用中间件捕获异常

通常情况下，如果用户定义的函数内抛出了异常（包括 `message` 等事件），会返回到框架基层去返回默认定义的内容。如果想自己捕获可以使用 `try/catch` ，但不方便复用，多处使用的话就需要重复写代码。这里可以使用中间件的异常处理方便地捕获错误。这个函数写到中间件类里即可

```php
/**
 * @HandleException(\Exception::class)
 * @param Exception|null $e
 */
public function onThrowing(?Exception $e) {
    ctx()->getResponse()->endWithStatus(500, "Error on this.");
}
```

这里的 `@HandleException` 中的参数为要捕获的类名，注意这里面的类名的命名空间需要写全称，不能上面 use 再使用，否则会无法找到异常类。

`ctx()` 为获取当前协程空间绑定的 `request` 和 `response` 对象。

