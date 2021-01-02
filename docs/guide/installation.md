# 安装

> 这篇为炸毛框架以及环境的部署教程。

框架部署分为环境部署和框架部署。框架部署非常简单，只需要通用的指令，下方主要说环境部署。

## Docker 部署 PHP 环境
如果你不想干扰主机的环境，可以使用 Docker 进行拉取框架适用的 PHP7 with Swoole Extension Docker Container。本框架安装教程中使用的 DockerHub 及 Dockerfile 构建文件所构建的容器均为独立的容器，和框架无关，此 Docker 也可以用作运行**其他基于 php-cli 模式的项目**。

方法一、直接拉取远程容器（推荐）
```bash
docker pull zmbot/swoole
```

方法二、从 Dockerfile 构建容器
```bash
git clone https://github.com/zhamao-robot/zhamao-swoole-docker.git
cd zhamao-swoole-docker/
docker build -t zm .
```

!!! note "从 Dockerfile 构建容器的提示"

    使用 Dockerfile 构建后，需要将下方所有的 `zmbot/swoole` 全部更换成 `zm`，或者你上方指令中的 `-t` 参数后方的名称，具体可以详情查阅 Docker 的文档。

## 主机部署 PHP 环境

### Debian 系列（Ubuntu、Kali ）

需要的系统内软件包为：`php php-dev php-mbstring gcc make openssl php-mbstring php-json php-curl php-mysql wget composer`

下面是一个一键安装的命令行（最小安装，需 root 权限）：

```bash
apt-get update && apt-get install -y software-properties-common && add-apt-repository ppa:ondrej/php && apt-get update && apt-get install php php-dev php-mbstring gcc make openssl php-mbstring php-json php-curl php-mysql -y && apt-get install wget composer -y && wget https://github.com/swoole/swoole-src/archive/v4.5.7.tar.gz && tar -zxvf v4.5.7.tar.gz && cd swoole-src-4.5.7/ && phpize && ./configure --enable-openssl --enable-mysqlnd && make -j2 && make install && (echo "extension=swoole.so" >> $(php -i | grep "Loaded Configuration File" | awk '{print $5}'))
```

### macOS (with Homebrew)

macOS 系统下的部署相对简单很多，只需要使用 Homebrew 安装以下包和执行安装命令即可

!!! note "给 macOS 开发者的提示"

    因为苹果新的 Apple Sillicon 对 Homebrew 的支持目前仅限于 Rosetta2 转译版，
    所以在使用 M1-based Mac 时出现问题暂时无解。
    使用以下指令可能会遇到报错等问题，如有疑问可直接使用 Docker 或咨询我（炸毛框架开发者）。

```bash
brew install php composer
pecl install swoole
```

### 其他 Linux 发行版

其他 Linux 发行版，如 CentOS，Fedora，Arch 等暂时还没有经过严格的测试需要哪些依赖，大体和 Ubuntu、Debian 系需要的依赖包差不多，可根据安装过程中报错提示依次安装，或者直接使用 Docker 环境。

## 安装框架

恭喜你，前方通过 Docker 或主机安装环境后可以开始构建框架的开发脚手架了！

如果你是通过**主机安装 PHP 部署的环境**，下方是通过脚手架来构建项目的命令行。

```bash
git clone https://github.com/zhamao-robot/zhamao-framework-starter.git
cd zhamao-framework-starter/
composer update
```

如果是通过 **Docker 部署的环境**，则需要在先克隆脚手架后在文件夹内使用 Docker 命令下的 `composer update`。

```bash
git clone https://github.com/zhamao-robot/zhamao-framework-starter.git
cd zhamao-framework-starter/
docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole composer update
```

或者在 Docker 环境下，你可以直接使用如下方法拉取和快速启动一个最标准的框架。

```bash
git clone https://github.com/zhamao-robot/zhamao-framework-starter.git 
cd zhamao-framework-starter
./run-docker.sh # 在正式版炸毛框架 v2 发布后可用，测试版暂不放出
```


## 启动框架
本地环境启动方式：
```bash
cd zhamao-framework-starter
vendor/bin/start server
```

使用 Docker 启动：
```bash
cd zhamao-framework-starter
docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole vendor/bin/start server
```

启动后你会看到和下方类似的初始化内容，表明启动成功了

```verilog
$ vendor/bin/start server
host: 0.0.0.0       |   port: 20001
log_level: 2        |   version: 2.0.0
config: global.php  |   worker_num: 4
working_dir: /Users/jerry/project/git-project/zhamao-framework
 ______
|__  / |__   __ _ _ __ ___   __ _  ___
  / /| '_ \ / _` | '_ ` _ \ / _` |/ _ \
 / /_| | | | (_| | | | | | | (_| | (_) |
/____|_| |_|\__,_|_| |_| |_|\__,_|\___/

[14:27:31] [I] [#0] Worker #0 启动中
[14:27:31] [I] [#2] Worker #2 启动中
[14:27:31] [I] [#1] Worker #1 启动中
[14:27:31] [I] [#3] Worker #3 启动中
[14:27:31] [S] [#3] Worker #3 已启动
[14:27:31] [S] [#0] Worker #0 已启动
[14:27:31] [S] [#2] Worker #2 已启动
[14:27:31] [S] [#1] Worker #1 已启动
```

单纯运行 炸毛框架 后，如果不部署或安装启动任何机器人客户端的话，仅仅相当于启动了一个 监听 20001 端口的WebSoket + HTTP 服务器。你可以通过浏览器访问：http://127.0.0.1:20001 ，或者你部署到了服务器后需要输入服务器地址。

!!! note "安装和部署总结"

	根据上方描述，此文档中剩余提到的所有 Bash 命令，如果使用 Docker 部署环境，则需要加上 Docker 环境的指令：`docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole`，如执行其他 Linux 指令（以查看 PHP 版本为例）：`docker run -it --rm -v $(pwd):/app/ -p 20001:20001 zmbot/swoole php -v`。

## 使用 IDE 等工具开发代码

我们使用文本编辑器进行炸毛框架开发，在使用集成开发环境 **IDEA** 或 **PhpStorm** 时，推荐通过插件市场搜索并安装 **PHP Annotations** 插件以提供注解命名空间自动补全、注解属性代码提醒、注解类跳转等，非常有助于提升开发效率的功能。

## 进阶环境部署和开发
炸毛框架还支持更多种启动方式，如源码模式、守护进程模式，具体后续有关环境和部署的进阶教程，请查看 [进阶开发](/advanced/) 部分！