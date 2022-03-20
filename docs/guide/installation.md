# 安装

> 这篇为炸毛框架以及环境的部署教程。

框架部署分为两部分，一部分是安装 PHP 环境，另一部分是通过 Composer 或 GitHub 拉取框架的脚手架。

## 一键下载静态 PHP 环境和框架脚手架

从 2.4.4 版本起，炸毛框架支持一键拉取一个静态的 PHP 运行时和脚手架，只需运行下面的脚本即可。（开发环境推荐此方法）

```bash
# 将会把 PHP、框架都安装在此目录下
mkdir zhamao-app/ # 这里可以取自己的项目名字
cd zhamao-app/
bash -c "$(curl -fsSL https://api.zhamao.xin/go.sh)"

# 安装完成后的启动框架命令（2.5.0 版本后可省略掉 runtime/php 前缀）
vendor/bin/start server

# 扩展用法：使用静态 PHP 版本的 Composer update
runtime/composer update
# 扩展用法：使用静态 PHP 运行别的 CLI 脚本
runtime/php path/to/your/script.php
```

> 有关静态 PHP 的多种用法（如 Composer），见 [进阶 - PHP 环境高级](/advanced/php-env)

## 使用 Docker 部署 PHP 和框架
你也可以使用 Docker 进行拉取 PHP 环境。

```bash
# 拉取 Docker 镜像
docker pull zmbot/swoole

# 再通过 GitHub 或其他方式拉取框架脚手架
git clone --depth=1 https://github.com/zhamao-robot/zhamao-framework-starter.git
cd zhamao-framework-starter/

# Docker 内使用 Composer 更新依赖
docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole composer update
docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole vendor/bin/start init

# 使用 Docker 启动框架
docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole vendor/bin/start server
```


启动后你会看到和下方类似的初始化内容，表明启动成功了

```verilog
$ vendor/bin/start server
=================================================================
 working_dir: /app/zhamao-framework-starter
 listen: 0.0.0.0:20001         |  worker: 4 (auto)
 environment: default          |  log_level: 2
 version: 2.7.0                |  master_pid: 28449
=================================================================
   ______
  |__  / |__   __ _ _ __ ___   __ _  ___
    / /| '_ \ / _` | '_ ` _ \ / _` |/ _ \
   / /_| | | | (_| | | | | | | (_| | (_) |
  /____|_| |_|\__,_|_| |_| |_|\__,_|\___/

[03-20 22:30:56] [S] [#1] Worker #1 started
[03-20 22:30:56] [S] [#2] Worker #2 started
[03-20 22:30:56] [S] [#3] Worker #3 started
[03-20 22:30:56] [S] [#0] Worker #0 started
```

单纯运行 炸毛框架 后，如果不部署或安装启动任何机器人客户端的话，仅仅相当于启动了一个 监听 20001 端口的WebSoket + HTTP 服务器。你可以通过浏览器访问：http://127.0.0.1:20001 ，或者你部署到了服务器后需要输入服务器地址。

## 命令总结

1. 对于框架的启动，必须 cd 到项目的跟目录，比如 `cd zhamao-app/` 进入到项目根目录。
2. 无论何种方式启动，启动框架的命令格式都为这个格式：`{php二进制路径} vendor/bin/start server {--如果需要参数的话这样跟}`
3. 第二条的 `php 二进制路径` 指的是，比如使用第一种静态 PHP 环境，这里写 `runtime/php` 就好了，如果是安装到系统的 PHP 的话，这里为空，如果是 Docker 部署的环境，则这里填 `docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole`

## 使用 IDE 等工具开发代码

我们使用文本编辑器进行炸毛框架开发，在使用集成开发环境 **IDEA** 或 **PhpStorm** 时，推荐通过插件市场搜索并安装 **PHP Annotations** 插件以提供注解命名空间自动补全、注解属性代码提醒、注解类跳转等，非常有助于提升开发效率的功能。

## 进阶环境部署和开发
炸毛框架还支持更多种启动方式，如源码模式、守护进程模式，具体后续有关环境和部署的进阶教程，请查看 [进阶开发](/advanced/) 部分！