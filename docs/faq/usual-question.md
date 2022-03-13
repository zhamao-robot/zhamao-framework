# 框架常见问题（持续更新）

## 如何正确地强制退出炸毛框架？

首先要知道一个概念，炸毛框架和传统的 PHP 以及其他如 Python 等语言的轻量框架都不同，框架启动后会依次启动 Master、Manager、Worker 等多个进程，而用户启动时入口的 PHP 进程就是 Master 进程，在一些对框架的正常中止、热重启上，我们给 Master 进程发送相应的 Linux 信号（如 SIGTERM）即可对整个框架的多个进程生效，无需给每个进程发送。

但是如果因为用户的误操作，导致炸毛框架其中的一个或多个进程阻塞，或者比如将框架挂在 screen 等守护但是守护服务进程被杀掉，总之就是无法使用 Ctrl+C 的方式正常关闭框架，这时就需要正确地杀掉所有框架进程（这固然可能会造成内存的缓存数据丢失）。

### v2.7.0 及以上版本教程

- 安全关框架指令：`./zhamao server:stop`
- 万能杀死所有框架进程指令：`./zhamao server:stop --force`
- 监视框架是否在运行：`./zhamao server:status`
- Worker 进程卡死：连续按 5 次 Ctrl+C 即可强行杀掉所有进程（SIGKILL）

### v2.6.6 及以下版本教程

!!! warning "注意"

    下方涉及 `ps` 命令后使用 `grep` 过滤的框架进程方式，如果你的服务器同时有其他使用 PHP 启动的服务，命令行刚好有 `server` 字样，可能会导致误杀，如果有影响的话，建议将 `grep server` 换成你启动时命令行的特殊参数或手动排除！

**一、**首先，使用 `ps`、`htop`、`netstat -nlp` 等命令确定框架的入口进程（也就是 Master 进程的 pid）。

确认方式示例如下：

- 如果你使用的是 >=2.4 版本的框架，在框架启动时就会在最先开始的 motd 上方显示 `master_pid`，如果你还能找到此处的显示，那么恭喜你，可以直接进行下面的第二步。
- 如果你不能正常通过框架的方式找到 pid，可以通过命令 `ps aux | grep php | grep server` 的方式找到框架所有的进程。其中列出的相关框架的进程，可以寻找 pid 最小的进程，即为 Master 进程。关于如何区分进程对应关系，见本页 [使用 Linux 工具辨别框架进程]()。
- 如果你对 `ps` 不熟悉，可以使用 `htop` 工具，使用 `F5 Tree` 方式显示，并且使用 `F4` 的 Filter，过滤 `php` 或 `bin/start` 等字样，找到进程树。

**二、**然后，确定框架是否正常运行且正常流程关闭。

如果框架能正常运行，比如可以通过访问浏览器的 `http://地址:端口/httpTimer` 等 HTTP 路由，可以使用 `SIGINT` 或 `SIGTERM` 信号正常关闭框架。我们假设 Master 进程的 pid 为 31234：`kill -TERM 31234` 或 `kill -INT 31234`，如果稍后使用 `ps aux | grep php | grep server` 命令发现没有进程存在（排除掉 grep 自身的进程），说明可以正常关闭，此关闭方法为正常停止流程，即保存了 `LightCache` 等内存缓存持久化的数据。

如果以上方式没有任何效果，继续看第三步。

**三、**不能正常流程关闭，需要手动杀掉所有进程。

首先使用 `ps aux | grep php | grep server | grep -v grep | awk '{print $2}'` 列出框架所有进程的 pid，确认无误后，在此条命令后接 `| xargs kill -9` 即可：

```bash
# 列出进程，只显示包含php，只显示包含server，排除grep本身进程，显示第二列的pid，使用xargs循环kill这里面的进程
ps aux | grep php | grep server | grep -v grep | awk '{print $2}' | xargs kill -9
```

## 如何使用 Linux 工具查看框架进程状态？

框架有多个进程，有时候我们需要通过监视进程状态来确定框架是否正常运行或查看框架的资源占用率。首先一个大概念，老生常谈，炸毛框架由 Master、Manager、Worker（、TaskWorker）进程组成的。

如果使用 htop 工具，就比较简单，比如我启动了一个应用，使用炸毛框架编写的垃圾分类小程序 API 服务器，在 htop 命令后找到如图这部分（下面的树状图是按 F5 后切换为树状显示，避免进程刷太快可以输入 `Shift+z`）：

![image-20210708003903652](https://static.zhamao.me/images/docs/image-20210708003903652.png)

其中，`-zsh` 下有唯一一个 php 进程，在图中对应的第一列 pid 为 `16258`，代表 Master 进程。

Master 进程下的唯一一个子进程（白色的是进程，绿色是线程），在图中对应的 pid 为 `16263`，代表 Manager 进程，用作管理 Worker 进程。

Manager 进程下的子进程，连号部分为对应的 Worker 进程，比如图中的 `16266`，`16267`，`16268`，`16269` 分别代表 `Worker #0`，`Worker #1`，`Worker #2`，`Worker #3` 四个 Worker 进程。

如果你还设置了 TaskWorker 进程，TaskWorker 进程的 pid 会和 Worker 进程一样是连续的，一般会接在 Worker 进程后面。

`htop` 使用方向键选择进程，选择到对应进程后可以使用 `F9` 来选择 kill 指令，比如让框架热重启，可以将光标移到 Master 进程上，使用 `SIGUSR1`：

![image-20210708004921655](https://static.zhamao.me/images/docs/image-20210708004921655.png)



