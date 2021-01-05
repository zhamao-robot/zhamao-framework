# ZMAtomic 原子计数器

原子计数器是用于多进程间跨进程使用的原子计数使用的，比如统计入站请求数量等。此功能基于 Swoole 的 Atomic，详情见 [Swoole - 文档]([进程间无锁计数器(Atomic) (swoole.com)](http://wiki.swoole.com/#/memory/atomic))。

## 配置和初始化

见配置文件：`config/global.php` 中的 `init_atomics` 字段：

```php
/** zhamao-framework在框架启动时初始化的atomic们 */
$config['init_atomics'] = [
    'foo' => 0,
    'bar' => 4,
];
```

这时我们就成功初始化两个原子计数器，名字分别为 `foo` 和 `bar`。

!!! warning "注意"

	初始化的值必须是不小于 0 的 int32 值！


## 使用

定义和命名空间：`ZM\Store\ZMAtomic`

连接计数示例：

```php
<?php
namespace Module\Example;
use ZM\Annotation\Swoole\OnRequestEvent;
use ZM\Store\ZMAtomic;
class Hello {
    /**
     * @OnRequestEvent()
     */
    public function onRequest() {
        $cnt = ZMAtomic::get("foo")->add(1);
        ctx()->getResponse()->end("当前已访问：".$cnt."次");
    }
}
```

### ZMAtomic::get()->get()

获取计数的数字：`dump(ZMAtomic::get("bar")->get());` 返回 4。

### ZMAtomic::get()->add($num)

加上一定的数并返回结果：`dump(ZMAtomic::get("bar")->add(5));` 返回 9。

### ZMAtomic::get()->sub($num)

要减少的数值（必须为正整数）：`dump(ZMAtomic::get("bar")->sub(5));` 返回 5。

### ZMAtomic::get()->set($num)

设置计数的数字：`ZMAtomic::get("bar")->set(77);`

!!! note "提示"

	还有一些不常用的方法，可以看 Swoole 官方的文档，这里就不一一列举了。

