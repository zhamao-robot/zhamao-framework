# 框架的多进程

首先对于多进程概念，对于传统 PHP 程序员可能比较陌生，唯一接触到的地方可能就是 php-fpm 等一些方式处理时间长的请求时开进程去执行。关于多进程，我觉得廖雪峰的 Python 多进程这段讲的不错：

> Unix/Linux 操作系统提供了一个`fork()`系统调用，它非常特殊。普通的函数调用，调用一次，返回一次，但是`fork()`调用一次，
> 返回两次，因为操作系统自动把当前进程（称为父进程）复制了一份（称为子进程），然后，分别在父进程和子进程内返回。

这里面的重点在于，多进程的创建，是父进程的复制，然后两个进程接下来运行的代码和存的内容就分道扬镳了。

PHP 也是如此，框架的多进程又是怎么一回事呢？为什么要采用多进程呢？

## 作用

使用过框架的你一定知道，框架是以命令行方式运行 PHP 的，而命令行方式运行 PHP，就代表要常驻内存，就像 Python、Node.js 一样。
而默认情况下，比如 Python 的 Flask 为单线程单进程模式，也就是说同时只能处理一个 Web 请求。
但大部分情况下，比如 Node.js，提供的都是异步 I/O，这也就是说明它在 Web 处理请求上，可同时承接的 I/O 密集型请求会更多一些，
这样在对一般的 Web 应用中 I/O 密集型场景非常有用，而且往往只需要单进程也可以承载上万的并发请求。

在炸毛框架中，因为框架基于 Swoole、Workerman 等驱动构建，在使用 Swoole 驱动时可以将一部分 I/O 操作协程化。
协程就是针对 I/O 操作进行一个调度，类似异步的 Node.js，所以针对项目中存在太多的 SQL 语句执行、文件读写的话，只需换成 Swoole 驱动，无需做任何修改，也可以达到很好的性能。

**但是**，CPU 密集型的应用或 Workerman 怎么办呢？假设我的 Web 应用有大量的排序、md5 运算怎么办呢？
这样的阻塞，假设是一个超级大的 for 循环或者是要执行很长时间的 while 循环，CPU 一直在被占用。多进程就是针对 CPU 密集型的应用说 yes 的一个方案。

## 多进程类型

框架多进程中，所有进程的功能和名称是有区别的。

- Master 进程：主进程，负责执行最初的启动代码，也是接下来其他类型进程的父进程，它一般不会执行任何业务代码，只是负责管理其他进程。
- Manager 进程：在使用 Swoole 驱动且使用了 `SWOOLE_PROCESS` 模式启动框架后，会出现，由 Master 进程 fork 而来，用于管理 Worker 进程。
- Worker 进程：主要逻辑的工作进程，用户态代码在这里被加载。在 Swoole 驱动的 `SWOOLE_PROCESS` 模式下由 Manager 进程 fork 而来，在 Workerman 驱动下由 Master 进程 fork 而来。
- TaskWorker 进程：在使用 Swoole 驱动且设置了 `taskworker_num` 时，由 Master 或 Manager 进程 fork 而来，用于处理耗时的任务。
- User 进程：在指定了 UserProcessStartEvent 事件下，驱动抽象层会调用驱动的进程创建方法，创建一个用户自定义的子进程，由 Master 或 Manager 进程 fork 而来。

## 框架可用的进程模式

首先，如果按照“指南”章节中的安装和配置使用框架，则框架默认的进程为单 Worker 模式。

现在框架支持的多进程模式有以下几种（`n > 1`）：

1. `MST1#1`：Workerman 的单 Worker 模式，也是框架默认启动的模式。
2. `MST1#n`：Workerman 的多 Worker 模式，由 Master 进程 fork 出多个 Worker 进程，`n` 为 Worker 进程数。
3. `MST1#0`：Workerman 的无 Worker 模式（在 Windows 上使用的默认模式），用户态代码在 Master 进程中执行，此时 Master 进程也是 Worker #0 进程。
4. `MST1MAN1#1`：Swoole 的 `SWOOLE_PROCESS` 启动模式下的单 Worker 模式，如果切换驱动为 Swoole 时，此模式为框架默认的启动模式。
5. `MST1MAN1#n`：Swoole 的 `SWOOLE_PROCESS` 启动模式下的多 Worker 模式，由 Manager 进程 fork 出多个 Worker 进程，`n` 为 Worker 进程数。
6. `MST1MAN0#0`：Swoole 的 `SWOOLE_BASE` 启动模式下的无 Worker 模式，用户态代码在 Master 进程中执行，此时 Master 进程也是 Worker #0 进程。
7. `MST1MAN0#1`：Swoole 的 `SWOOLE_BASE` 启动模式下的单 Worker 模式，如果切换驱动为 Swoole 时，此模式下仅有 Master、Worker #0 两个进程存在。
8. `MST1MAN0#n`：Swoole 的 `SWOOLE_BASE` 启动模式下的多 Worker 模式，类似于 `MST1#n`，由 Master 进程 fork 出多个 Worker 进程，`n` 为 Worker 进程数。

::: tip 提示

- 在 Windows 环境（MSVC 环境的 PHP），框架目前只能使用 Workerman 驱动并使用 `MST1#0` 模式。
- 在 Linux、macOS 环境，使用 Workerman 驱动时，由于 Workerman 自身的限制，无法使用 `MST1#0` 模式。

:::

### 框架为什么使用单 Worker 模式

炸毛框架从最初的炸毛机器人、炸毛框架 0.x、1.x、2.x 到现在的 v3 版本，一直在探索最合适的进程模式。

炸毛机器人本体使用了单 Worker 模式，原因：便于热更新（重载 reload 功能），机器人的逻辑代码在 Worker 进程中执行，重载时只需要重启 Worker 进程即可。

框架的项目还在叫 cqbot-swoole 时，采用的是无 Worker 模式，重载应用不是很方便。
框架 1.x 延续了现在炸毛机器人本体的进程模式，但 2.x 发生了变化。框架 2.x 默认使用 Swoole 作为底层驱动且默认使用多 Worker 模式启动。
主要原因是想充分利用 Swoole 的特性以及提升框架的性能上限。 
但在 2.x 的开发者调研情况来看，使用多进程在开发层面带来的不便远远大于性能上的提升，因此框架 3.x 继续默认使用单 Worker 模式启动。

但我们总不能在新版本对特性做出退步，总有需要多 Worker 或单进程（即 `MST1#0` 和 `MST1MAN0#0`）的时候。
所以在框架 3.0 全新的大版本中，我们对多进程本身也加入了支持，但是默认仍然是单 Worker。

> 单 Worker 不是单进程，单 Worker 是至少有两个进程，一个 Master、一个 Worker。如果是 Swoole，还可能有 Manager。

- 单进程：适合任意环境。
- 单 Worker：适合生产环境和开发环境，同时也便于重载。
- 多进程：适合生产环境。

在使用单进程模式时，调试代码变得十分容易，比如使用 psysh 下断点将是非常稳定可靠的，因为只有一个进程在运行。
单 Worker 模式做到了用户态代码与主进程隔离，方便重载，同时也有一定的便捷性，比如可以在 Worker 进程使用全局变量和静态成员变量等。

## 多进程的内存隔离

多进程模式下有内存隔离，而且各个进程的父子关系也很明确。进程是程序在操作系统中的一个边界，和自己有关的一切变量、内容和代码都在自己的进程内。
不同进程之间如果不使用管道等方式，是不可以互相访问的。而加上开始描述的，创建子进程是一个复制自身的过程，所以也就会有如下图的情况：

![多进程-内存隔离](https://img.zhamao.xin/framework/multi-process-variable.png)

我们以静态类为例，设置一个进程中的全局变量。这里就会出现，同一个静态变量在多个进程中完全不同的值的结果。
此后，我们将会在 Worker 进程中执行用户的代码。
如果设置 Worker 数量仅为 1 的话，那么就简单许多了，你还是可以使用全局变量或静态类来存储你想要的内容而不用担心这种多个进程变量隔离的情况，
因为用户的 Web 请求处理的代码只会在一个 Worker 进程中执行。
如果像设置了多个 Worker，则收到的机器人事件或 HTTP 请求等就有可能出现在不同的 Worker 进程中，给全局变量设值就一定会造成不同步的问题。
这时我们就不可以使用全局变量做数据同步（注意，我说的是数据同步）。

如果想实现跨进程通信，也有很多种方案，有几种方案是炸毛推荐的：

- 使用 Redis、SQL 等数据库。例如使用 Redis 后，你可以把需要的数据写入 Redis，再方便地通过框架地 KV 接口无损从 LightCache 切换以实现跨进程通信。
- 使用 Swoole 驱动并在 Setup 阶段设置 Atomic、Swoole Table 等共享内存的组件，方便跨进程通信。
- 使用 Swoole 的 PipeMessage 也可以直接方便地在多个 Worker 之间相互通信，但目前 [php-libonebot](https://github.com/botuniverse/php-libonebot) 暂无支持的计划。
