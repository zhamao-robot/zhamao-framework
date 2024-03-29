# 框架调试 - 热更新和重载

::: danger 

目前此功能还在开发中，暂不可用。

:::

框架使用了 Workerman、Swoole、Choir 等驱动作为底层协议和进程管理模型，在使用了 Worker 进程模式启动框架后，你可以使用 Worker 进程的重载功能来更新你的代码。

首先，如果你不了解框架的进程结构，请先了解 [进阶开发 - 框架的多进程](/advanced/multi-process)。

开发者在使用框架开发相应的插件时，经常需要重新载入插件的代码。热更新和重载就是为了在不停止主进程的情况下在 Worker 进程内重新加载代码，以达到代码更新的作用。
你在插件目录开发的插件代码，一般为 `plugins/xxx/` 目录下的代码，均可使用重载功能实现热更新。

::: warning 注意

- Linux、macOS 环境使用 Workerman、Swoole 驱动默认配置情况均可使用重载。
- Swoole 驱动模式下使用 SWOOLE_BASE 模式，且未设置 Worker 数量时不可使用重载。
- Workerman 驱动模式下除 Windows 外均可使用重载。

:::

使用重载的方式很简单，在另一个终端内进入框架的工作目录，并执行命令：

```bash
./zhamao server:reload
```

或者你也可以在代码中调用 `\ZM\Framework::getInstance()->reload()` 进行重载。

如果你不想手动调用重载命令或代码，你也可以在启动框架时使用 `--watch` 参数来监听 plugins 目录文件变化。

在使用 `--watch` 启动热更新功能后，框架将每 3 秒比较一次文件变化（不包含插件内的 vendor 第三方库目录）。涉及更新到 `.php` 文件的，将会自动重载一次。
重载后，所有插件都会按照正常启动流程执行一次，例如执行 `@Init` 注解等。
