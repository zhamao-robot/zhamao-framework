# ZMUtil 杂项工具类

调用前先 use：`use ZM\Utils\ZMUtil;`

## ZMUtil::stop()

停止框架运行。

## ZMUtil::reload()

重载框架，这会断开所有到框架的连接和重载所有 `src/` 目录下的用户源码并重新加载所有 Worker 进程。

## ZMUtil::getModInstance()

根据类名称拿到此类的单例（前提是目标的类的构造函数为空）。

```php
class ASD{
    public $test = 0;
}
ZMUtil::getModInstance(ASD::class)->test = 5;
```

