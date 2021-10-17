# 协程池

首先要声明的一点是，协程池这个概念是我自己编的。

因为协程的特点，它是单线程下运行的，所以在一个进程内同时实际上只会有一个协程的代码在执行逻辑，但是后面的 IO 操作、协程挂起等待的操作都是同时去做的，比如数据库的大数据读取、写入需要耗时几秒甚至几十秒，这时用基于协程的 MySQL 连接池就完全不是问题。

但是就拿 MySQL 举例，我们 MySQL 使用的是 TCP 连接，而无论是 MySQL 还是 TCP 连接，最大数量都是有限的。我们即使设置了允许最大协程数量非常大，比如上百万，但是也不能让数据库连接池一个池支持上百万的连接。

这时假设高并发进来了怎么办呢？这时就需要框架提出的一个折中方案：协程池了。

顾名思义，协程池是一个容纳协程的区域，而协程里又容纳着各种各样需要阻塞调用被协程调用的 IO 操作，协程池用作限制协程的数量。

```php
use ZM\Utils\CoroutinePool;
use ZM\DB\DB;

// 传统写法，一旦高并发则可能导致 Too many connections
go(funuction(){
    DB::rawQuery("INSERT INTO users VALUES(?,?)", ["admin", "password"]);
});
// 协程池写法
CoroutinePool::go(function(){
    DB::rawQuery("INSERT INTO users VALUES(?,?)", ["admin", "password"]);
}, "foo");
```

参数：`go(callable $func, $name = "default")`

`$name` 为协程池对应的名字，你可以设置多个协程池，用来支持不同的需要限制并发 IO 数量的地方，例如 Redis 和 MySQL 设置不同的名字。`$func` 可为闭包或可调用的方法名称或数组。

## 配置

默认情况下，直接调用 `CoroutinePool::go()` 时，协程池大小为 30，也就是如果有 30 个协程进入了挂起状态（比如数据库在执行查询语句），那么更多的协程执行时就会阻塞并以协程等待的方式等待，直到现有的 30 个协程中的一部分完成了它的工作。

## 方法

### CoroutinePool::go()

将协程放入协程池运行。

如果不写 `$name` 参数，则使用的是默认协程池。

### CoroutinePool::defaultSize()

设置默认协程池的大小（默认 30）

```php
CoroutinePool::defaultSize(64);
for($i = 0; $i < 1000; ++$i) {
    CoroutinePool::go(function(){
        DB::rawQuery("SELECT * FROM users");
    });
}
```

### CoroutinePool::setSize()

定义：`setSize($name, int $size)`

`$name` 为字符串，是你要用的协程池的名称。

`$size` 为大小，最大不可超过 Swoole 配置文件中指定的最大协程数量。

