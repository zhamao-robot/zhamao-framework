# FAQ

这里会写一些常见的疑难解答。

## 启动时报错 Address already in use

1. 检查是否开启了两次框架，每个端口只能开启一个框架。
2. 如果是之前已经在 20001 端口或者你设置了别的应用同样占用此端口，更换配置文件 `global.php` 中的 port 即可。
3. 如果是之前框架成功启动，但是使用 Ctrl+C 停止后再次启动导致的报错，请根据下面的步骤来检查是否存在僵尸进程。

- 如果系统内装有 `htop`，可以直接在 `htop` 中开启 Tree 模式并使用 filter 过滤 php，检查残留的框架进程。
- 如果系统没有 `htop`，使用 `ps aux | grep vendor/bin/start | grep -v grep` 如果存在进程，请使用以下命令尝试杀掉：
  
```bash
# 如果确定框架的数据都已保存且没有需要保存的缓存数据，直接杀掉 SIGKILL 即可，输入下面这条
ps aux | grep vendor/bin/start | grep -v grep | awk '{print $2}' | xargs kill -9

# 如果不确定框架是不是还继续运行，想尝试正常关闭（走一遍储存保存数据的事件），使用下面这条
# 首先使用 'ps aux | grep vendor/bin/start | grep -v grep' 找到进程中第二列最小的pid
# 然后使用下面的这条命令，假设最小的pid是23643
kill -INT 23643
# 如果使用 ps aux 看不到框架相关进程，证明关闭成功，否则需要使用第一条强行杀死
```

## 出现 deadlock 字样

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

## 使用 LightCache 关闭时无法正常保存持久化

LightCache 因为是跨内存使用的，所以每次重启和关闭框架时，都只会让其中一个进程去保存。因为在 2.4.2 版本开始，持久化的逻辑发生了更改，不再支持 `expire = -2` 进行设置持久化（因为那样会很容易让开发者写错），仅支持使用 `LightCache::addPersistence($key)` 这样的方式进行设置持久化，所以在 2.4.2 版本以后，请使用此方法进行持久化设置，保证数据不丢失。

此外，2.4.2 版本起，不再支持用户手动调用 `savePersistence()` 方法，普通用户不可手动调用此方法，否则会导致数据出错。

## 