# SpinLock 自旋锁

前面讲到 LightCache 轻量缓存在特定的情况下为了保证数据不被多进程的因素导致丢失或覆盖，在高并发情况下修改数据需要加锁，所以炸毛框架内置了 SpinLock 自旋锁。

## 配置

自旋锁使用无需配置，和 LightCache 同源。

## 使用

定义：`ZM\Store\Lock\SpinLock`

### SpinLock::lock($key)

给信号量 `$key` 上锁。如果该信号量已经被上锁，则原地等待直到其他资源释放锁。

```php
SpinLock::lock("foo");
```

### SpinLock::unlock($key)

给信号量 `$key` 释放锁。

```php
SpinLock::unlock("foo");
```

### SpinLock::tryLock($key)

给信号量 `$key` 上锁。如果该信号量已经被上锁，则立刻返回 false。

```php
SpinLock::lock("foo");
```

## 综合实例

我们这里以之前在 LightCache 中的实例进行继续讲解，如何给之前那样的情况加锁：

```php
/**
 * @RequestMapping("/test")
 */
public function test() {
    SpinLock::lock("web_count");      // 加上这行
    $s = LightCache::get("web_count");
    if($s === null) $s = 1;
    else $s += 1;
    LightCache::set("web_count", $s);
    SpinLock::unlock("web_count");    // 再加上这行
    return "<h1>It works!</h1>";
}
```

加两行就 OK。这时再使用压测工具请求 200000 次，值就会是 200000 了！

原理剖析：在 LightCache 获取前，先对此内容上锁，这时如果其他进程有同时也在执行这个代码的时候，就会在 `SpinLock::lock()` 这行代码处原地等待，防止继续执行。等前面的那个进程执行到 `SpinLock::unlock()` 释放锁时，其他进程才可继续执行，从而避免了多个进程并行执行这段代码导致的数据错乱。

!!! error "警告"

	使用锁时务必谨慎，如果不按照下面的规则使用自旋锁可能导致 CPU 占用率上升。

自旋锁使用约定：

- 使用 `SpinLock::lock()` 指定信号量名称时必须指定为字符串，且最好与你的 LightCache 缓存名称相同。
- 使用 `lock()` 时最好紧跟在 `LightCache::get()` 代码前。
- 使用自旋锁后，`LightCache::get()` 到 `LightCache::set()` 之间的代码段一定不能有 **读写文件、数据库操作和网络请求** 等代码，最好为纯 PHP 逻辑代码，且越短越好，如示例代码。
- 在 `LightCache::set()` 后最好紧跟 `SpinLock::unlock()`。

## 性能

使用自旋锁几乎没有性能损失，自旋锁要比其他类型的锁性能强很多，在上方举例使用的 `ab` 压测工具测试 100万请求 下，使用自旋锁和不适用自旋锁的测试成绩时间分别为：7.4s 和 6.9s。