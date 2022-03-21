# 事件分发器（进阶）

事件分发器是以上所有注解事件执行函数的一个分发器，如果你在上一章已经学会了如何创建自定义注解，那么本章就来说明如何用内置的事件分发器进行分发自定义事件。

如果你不需要了解或自定义有关事件分发的功能，此处可无需阅读。

## 属性

- 类名：`ZM\Event\EventDispatcher`

## 方法

### EventDispatcher::interrupt()

阻断当前正在运行的事件，只能在事件内部被调用的函数中实现。

### __construct()

构造方法。

```php
EventDispatcher::__construct(string $class = '')
```

初始化一个事件分发器，可进行一系列设置，对事件分发做限定。

#### 参数

`$class`：设置要分发的事件对应的注解类名，支持自定义注解（例如 `CQMessage::class`）

### setRuleFunction()

设置函数触发规则判定的函数（就是在执行事件函数前执行的规则判定）

```php
setRuleFunction(callable $rule = null)
```

#### 参数

`$rule`：支持回调或闭包。闭包的参数为执行对应事件函数所绑定的注解事件对象。

```php
$dispatcher = new EventDispatcher(CustomEvent::class);
$dispatcher->setRuleFunction(function($obj) {
    return $obj->name == "zhamao" ? true : false;
});
```

上方的 `$obj` 就是 CustomEvent 类的实例，参数绑定为注解中对应的参数。

### setResultFunction()

设置事件函数返回值处理的回调函数。

```php
setReturnFunction(callable $return_func)
```

#### 参数

`$return_func`：设置事件函数返回值处理的回调函数，回调参数绑定为对应单独事件函数的返回值。

```php
$dispatcher = new EventDispatcher(CustomEvent::class);
$dispatcher->setReturnFunction(function($return) {
    if (is_string($return)) Console::info("函数返回了 ".$return);
});
```

### dispatchEvents()

开始分发事件。

```php
dispatchEvents(...$params)
```

#### 参数

自定义参数，这里填入的参数将被填入被分发的函数参数中。

```php
$dispatcher->dispatchEvents("foo", "bar");
```

```php
<?php
class Test {
    /**
     * @CustomEvent("zhamao")
     */
    public function test($arg1, $arg2) {
        echo "$arg1: $arg2"; //将输出 "foo: bar"
    }
}
```

## 机制

事件分发器的机制说简单不简单，说复杂也不复杂，它和中间件有着非常大的关系，因为它会自动检测和识别所要执行的函数有没有中间件，并且根据顺序进行执行。

在炸毛框架内部，一个完整的事件流程和中间件的关系如下图：

![Untitled Diagram](https://static.zhamao.me/images/docs/dbb4e32e1c77f96162d10e41f25befa4.png)

对于同一事件的优先级和响应顺序，优先级的关系如下图：

![diagram](https://static.zhamao.me/images/docs/fa52005b7ca891053617a77541c7e785.png)

对于事件内单个事件被调用的单个函数下如果存在多个中间件，中间件模型和事件的关系如下图：

![Untitled Diagram-2](https://static.zhamao.me/images/docs/16ce39caad472d03d7786e6ffb0c55bf.png)

## 实战例子

我们假设 CustomEvent 是我们的自定义注解。还没写完，这部分太复杂了，而且举例子也不好举例，这块应该也不用着急更新。

TODO：待完成
