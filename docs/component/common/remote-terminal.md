# 远程终端

框架在 2.3 版本时删除了本地终端（就是框架启动后可以在终端输入一些参数），因为框架的多进程模式会导致终端输入错乱，所以暂时取消掉了。

而远程终端应运而生，为的是弥补这一功能。与之前不同的是，远程终端使用 nc 连接，无需任何其他组件和客户端，而且功能更丰富，支持自定义命令。

## 启用

有两种开启方式：

- 永久开启：全局配置文件中找到 `remote_terminal` 的 `status`，改为 true，启动框架即可。
- 临时开启：启动框架时加上参数 `--remote-terminal`。例如：`vendor/bin/start server --remote-terminal`。

## 配置

在一般情况下，框架为了安全，直接按照默认配置，会监听 `127.0.0.1:20002` 端口，不可以远程访问，只能使用本机的 nc 连接，效果如下：

本地主机：

![img.png](https://static.zhamao.me/images/docs/3432551c08b34ca10aaf19f3f82aedeb.png)

从别的主机：

![img.png](https://static.zhamao.me/images/docs/6f35f2745d66c7e186da75b6f09248c2.png)

如果将 `host` 改为 `0.0.0.0` 或对应监听地址，即可指向性访问。

但是，如果你又想远程连接，又想保证安全，那么可以设置一个 token 参数，来保证连接时需要输入 token 才能使用远程终端。
假设我们的 token 是 `iAMTokEn`：

![img.png](https://static.zhamao.me/images/docs/e502af4c0fd9359615548303cacb70dd.png)

## 使用

默认情况下，使用 `nc` 命令即可。

```bash
nc <your-host> <your-port> -vvv
# nc 127.0.0.1 20002 -vvv
```

输入 help 即可查看内置的常用指令：

![img.png](https://static.zhamao.me/images/docs/7b74aa2b487c86482097ec7692c66e08.png)
