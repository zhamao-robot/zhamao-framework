# LightCache 轻量缓存

在炸毛框架 1.x 时代，框架里有非常方便使用的 ZMBuf 缓存，但是由于 2.x 版本框架加入了多进程模式，所以不能再以传统的存到全局变量的方式来构建和管理缓存了，LightCache 就是替代方案。LightCache 依旧是 key-value 键值对形式的存储，支持多种类型的变量。

定义：`ZM\Store\LightCache`。

## 与 ZMBuf 的不同

从存储内容角度，LightCache 存入的是 Swoole 初始化的共享内存，基于 Swoole/Table 编写。优势在于多进程下的性能极佳，而且没有数据同步问题；劣势在于它需要在启动框架前就声明总大小，不能根据存储数据的大小来划定，需提前指定最大能存储的容量。而 ZMBuf 基于直接把变量存到静态成员中 `public static $data` 类似这样，且 1.x 框架基于单进程单线程，无任何数据同步的问题。

总之来说，LightCache 是让用户在涉及多进程编程时，一个折中的解决方案，提出和解决了很多多进程开发时存储数据遇到的问题：数据同步、进程间通信效率、数据是否需要上锁等。

- 数据同步：多进程下因为是固定的内存大小区域，所以每个进程读取和写入都是只有一份数据的，不存在数据不同步的问题。
- 进程间通信：因为多个进程共享一片区域的内存，所以不需要进程间通信，无协程切换。
- 镀锡是否需要上锁：看情况。一般情况下 Swoole/Table 模块自带一个行锁，只有两个进程在两个 CPU 上同时读取一行数据时才会发生抢锁，作为框架的使用者，如果只写或只读，是无需手动上任何锁的。只有在先 `get()` 再 `set()` 这样的情况才需要上自旋锁。后面的段会详细讲述。

使用体验上，基本和 ZMBuf 无差，如果没有用过 1.x 的版本，可无视此段话。

## 使用

### 配置和初始化

配置文件还是在 `config/global.php` 文件里，字段是 `light_cache`。

```php
/** 轻量字符串缓存，默认开启 */
$config['light_cache'] = [
    'size' => 512,                     //最多允许储存的条数（需要2的倍数）
    'max_strlen' => 32768,               //单行字符串最大长度（需要2的倍数）
    'hash_conflict_proportion' => 0.6,   //Hash冲突率（越大越好，但是需要的内存更多）
    'persistence_path' => $config['zm_data'].'_cache.json',
    'auto_save_interval' => 900
];
```

其中 `$size` 是最多保存的键值对数目，填写非 2 的倍数时底层会自动修正为 2 的倍数值。

`$max_strlen` 为单条值最长保存的长度。因为 Swoole/Table 只能存数字、字符串，所以在存取数组等变量时会先将其序列化为字符串形式保存，get 时自动反序列化回来。在存数组等非字符串变量时，请先自行计算你要存取的内容序列化后的最大长度。如果长度超出最大长度，则无法保存，`set()` 将返回 false。

`hash_conflict_proportion`：Table 模块底层使用 hash 表，会存在 hash 冲突，调大 Hash 冲突率会提升 `size` 指定条目数的准确性，但也将增加物理内存的使用。这里单位是百分比，`0.6` 为 `60%`。

`persistence_path` 是持久化保存变量的文件保存位置，默认在 `zm_data/_cache.json` 文件。

`auto_save_interval` 是持久化保存变量的自动保存时间，单位秒。

### LightCache::set()

设置内容。

定义：`LightCache::set($key, $value, $expire = -1)`

返回值：`bool`。当 value 超出了最大长度或内存不足时，返回 false，其余 true。

参数：

`$key` 的长度不能超过 64 字节，且不能存入二进制内容。

`$value` 可存入 `bool`、`string`、`int`、`array` 等可被 `json_encode()` 的变量，闭包函数和对象不可存入。

`$expire` 是 `int`，超时时间（秒）。如果设定了大于 0 的值，则表明是在 `$expire` 秒后自动删除（框架中途停止不受影响）。如果为 -1 则什么都不做。框架停止后自动被清除。

**注意：如果前面使用了 set() ，后面再次使用 set() 会重置 expire 过期时间为 -1（-1 是框架运行时不过期，关闭框架删除的状态），如果只需要更新值，请使用 update()。**

```php
// use ZM\Store\LightCache;
/**
 * @CQCommand("store")
 */
public function store() {
    LightCache::set("key1", ["value1" => "strOrInt", "value2" => 123]);
    return "OK!";
}
/**
 * @CQCommand("storeAfterRemove")
 */
public function storeAfterRemove() {
    LightCache::set("store1", "remove1", 30);
    ctx()->reply(LightCache::get("store1") !== null ? "内容存在！" : "内容不存在！");
    zm_sleep(30);
    ctx()->reply(LightCache::get("store1") !== null ? "内容存在！" : "内容不存在！");
}
```

<chat-box>
) store
( OK！
) storeAfterRemove
( 内容存在！
^ 等待 30 秒
( 内容不存在！
</chat-box>

### LightCache::update()

更新值而不更新状态。如果键值对不存在，则返回 false。

定义：`LightCache::update(string $key, $value)`

参数同 `set()`，可参考。

### LightCache::get()

获取内容。

返回值：`mixed|null`。当无内容或过期时返回 null，剩余情况返回原数据。

### LightCache::getExpire()

获取存储项剩余过期时间（秒）。

定义：`LightCache::getExpire(string $key)`

```php
$s = LightCache::set("test", "hello", 20);
zm_sleep(10);
dump(LightCache::getExpire("test")); // 返回 10
```

### LightCache::getExpireTS()

获取存储项要过期的时间戳。

定义：`LightCache::getExpireTS(string $key)`

```php
$s = LightCache::set("test", "hello", 20); //假设这条代码执行时时间戳是 1616838482
zm_sleep(10);
dump(LightCache::getExpire("test")); // 返回 1616838502
zm_sleep(10);
dump(LightCache::getExpire("test")); // 返回 null
```

### LightCache::getMemoryUsage()

获取轻量缓存使用的总空间大小（字节）

```php
LightCache::getMemoryUsage());
```

轻量缓存的内存手工计算方式：(Table 结构体长度` + `KEY 长度 64 字节 + `$size`) * (1 + `$conflict_proportion`) * 列尺寸。

Table 结构体长度根据你所设定的 `max_strlen` 会变化。

> 框架默认配置下的轻量缓存启动后大约占用内存 25MB 左右。

### LightCache::isset()

判断某项是否存在。

```php
LightCache::set("foo", "bar");
dump(LightCache::isset("foo")); // true
```

### LightCache::unset()

删除某项。

```php
LightCache::set("foo", "bar");
LightCache::unset("foo");
dump(LightCache::isset("foo")); // false
```

### LightCache::getAll()

获取所有项。

```php
LightCache::set("k1", ["I", "am", "array"]);
LightCache::set("k2", "v2");
LightCache::set("k3", 20001);
dump(LightCache::getAll());
/*
{
"k1": ["I", "am", "array"],
"k2": "v2",
"k3": 20001
}
*/
```

### LightCache::addPersistence()

添加持久化存储的键。

用法：`LightCache::addPersistence($key)`。

注：只需调用一次即可，无需多次重复调用，也不需要设置 expire 为 -2 了。（2.4.2 起可用此方法）。

详见下方 **持久化**。

### LightCache::removePersistence()

删除持久化的键。

用法：`LightCache::removePersistence($key)`。

注：只需调用一次即可，无需多次重复调用，也不需要设置 expire 为非 -2 了。（2.4.2 起可用此方法）。

### 持久化

使用 `LightCache::addPersistence($key)` 添加对应需要持久化的键名即可。

```php
/**
 * @OnStart()
 */
public function onStart() {
    LightCache::addPersistence("msg_time");
}
/**
 * @CQCommand("getStore")
 */
public function getStore() {
    return "存储时间：".date("Y-m-d H:i:s", LightCache::get("msg_time"));
}
```

<chat-box>
^ 我在 2021-01-05 15:21:00 发送这条消息
) getStore
( 2021-01-05 15:20:00
^ 这时我用 Ctrl+C 停止框架，过一会儿再启动
) getStore
( 存储时间：2021-01-05 15:20:00
</chat-box>

### 数据加锁

在特定情况下，使用 LightCache 必须配合锁使用，否则会出现数据错乱。我们来看下面的例子：

```php
/**
 * @RequestMapping("/test")
 */
public function test() {
    $s = LightCache::get("web_count");
    if($s === null) $s = 1;
    else $s += 1;
    LightCache::set("web_count", $s);
    return "<h1>It works!</h1>";
}
```

我们使用压测工具，例如 `ab`，对此路由接口开很多很多线程进行测试，假设我们设置请求总数为 200000 次，框架的工作进程数为 8（我用的是 2020 年末的 i5 MacBook Pro 13 inch）。

> 懒得再测了，下面就口述过程吧。

在运行完测试后，通过 `LightCache::get("web_count")`，获取到的数你会发现不是 200000。怎么回事呢？请自行翻阅多进程开发相关的书籍哦！（或者简单理解为，有一些情况下，进程 1 执行到了 `if-else` 语句，另一个进程也执行到了这里，两次在代码层面加的数是相同的，则虽然请求了两次，但是后执行 set 的那个进程又覆盖了前一个进程执行的值，导致最终结果加了 1 而不是 2）

!!! note "提示"

	同样的场景，使用 ZMAtomic 就不需要使用锁了。Atomic 是一句话：`add(1)` 立即加值的。而 LightCache 需要加锁的情况一般都是 `get->改值->set` 这样的代码。


解决这一问题，就需要用到锁。这种情况下，我们首先考虑的是自旋锁，框架也因此内置了一个方便使用的自旋锁组件。详见下一章：自旋锁。

## 如何临时缓存大变量

由于 LightCache 需要提前声明最大大小，所以在某些情况下，比如第三方 API 接口结果临时缓存，可能不太适合使用，这时对于 2.x 版本的多进程炸毛框架是一个新的问题。

解决方案有三种：

- 将 `global.php` 中的 `swoole.worker_num` 调整为 `1` 即可，所有除所有主 handler 事件的用户类外其他类均可使用如 `Hello::$store` 类似的静态变量全局存取
- 使用 WorkerCache（需要 2.2.0 以上版本）
- 使用 Redis（需要安装 `redis` 扩展）

以上，WorkerCache 是为了弥补 LightCache 的不足而诞生的，以下就是 WorkerCache 的具体内容。

### WorkerCache 跨进程大缓存

WorkerCache 和 LightCache 几乎完全不同，WorkerCache 存储的方式说白了就是 PHP 的静态变量，不过框架支持使用封装好的进程间通信进行跨进程读取。但由于需要设置一个存储变量的进程，所以配置文件必须先指定要将数据存到哪个 Worker/TaskWorker 进程中。关于框架内多进程的说明，请见 [进阶 - 多进程 Hack](/advanced/multi-process/)。

定义：`ZM\Store\WorkerCache`。

#### 配置

见 [基本配置](/guide/basic-config/)。

#### WorkerCache::get()

定义：`get($key)`。

`$key` 为指定要获取的键值对的值，如果不存在则返回 null。

#### WorkerCache::set()

定义：`set($key, $value, $async = false)`。

设置变量，你懂的。

注意，`$value` 可以是被无损 `json_encode` 和 `json_decode` 的变量，闭包（Closure）、资源（resource）等类型不支持存储。

`$async` 默认为 false，当为 true 时候，不会返回是否成功设置与否，否则会协程等待是否目标进程存储成功。

#### WorkerCache::unset()

定义：`unset($key, $async = false)` 

删除键对应的值。`$async` 的意义同上。

#### WorkerCache::add()

定义：`add($key, int $value, $async = false)`

给 int 类型的值加一，如果值不存在，则默认为 0 且加上目标的 `$value`。

#### WorkerCache::sub()

定义：`sub($key, int $value, $async = false)`

给 int 类型的值减一，如果值不存在，则默认为 0 且减去目标的 `$value`。

```php
<?php
namespace Module\Example;

use ZM\Store\WorkerCache;
use ZM\Annotation\CQ\CQCommand;

class Hello {
    /**
     * @CQCommand("set_store")
     */
    public function setStorage() {
        $arg1 = ctx()->getNextArg("请输入要设置的内容名称");
        $arg2 = ctx()->getFullArg("请输入要设置的内容");
        WorkerCache::set($arg1, $arg2);
        return "成功！";
    }
    
    /**
     * @CQCommand("get_store")
     */
    public function getStorage() {
        $arg1 = ctx()->getFullArg("请输入要获取的内容名称");
        $data = WorkerCache::get($arg1);
        return $data ?? "内容不存在！";
    }
}
```

<chat-box>
) set_store hello world
( 成功！
) get_store hello
( world
) get_store foo
( 内容不存在！
</chat-box>

