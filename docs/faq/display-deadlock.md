# 出现 deadlock 字样

一般情况下，如果误操作框架可能会报如下图的错误：

```
===================================================================
 [FATAL ERROR]: all coroutines (count: 1) are asleep - deadlock!
===================================================================

 [Coroutine-1]
--------------------------------------------------------------------
#0  Swoole\Coroutine\System::sleep() called at [/Users/jerry/project/git-project/zhamao-framework/src/ZM/global_functions.php:232]
#1  zm_sleep() called at [/Users/jerry/project/git-project/zhamao-framework/src/Module/Example/Hello.php:38]
#2  Module\Example\Hello->onStart() called at [/Users/jerry/project/git-project/zhamao-framework/src/ZM/Event/EventDispatcher.php:205]
#3  ZM\Event\EventDispatcher->dispatchEvent() called at [/Users/jerry/project/git-project/zhamao-framework/src/ZM/Event/EventDispatcher.php:89]
#4  ZM\Event\EventDispatcher->dispatchEvents() called at [/Users/jerry/project/git-project/zhamao-framework/src/ZM/Event/SwooleEvent/OnWorkerStart.php:130]
#5  ZM\Event\SwooleEvent\OnWorkerStart->onCall() called at [/Users/jerry/project/git-project/zhamao-framework/src/ZM/Framework.php:336]
```

这种错误的出现原因一般是因为协程未结束而 Worker 进程提前退出导致的，这个错误也可手动造成（在任意 Worker 进程内的位置使用 `zm_yield()` 且不使用 `zm_resume()` 恢复，期间使用 reload 或 stop 重启或停止框架就会报错）。

还有一种情况是数据库、文件读取或下载上传还没有传送结束，时间已经超时，在关闭或重启框架时不得不强行切断协程的运行。这种情况建议根据下方的打印输出栈进行插错，建议将协程运行时间长的过程缩短或调长 `swoole` 配置项下面的 `max_wait_time` 时间（秒），2.4.3 版本起此参数默认为 5 秒。