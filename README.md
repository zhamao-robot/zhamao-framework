# CQBot-swoole

## 此分支停止维护！

[![作者QQ](https://img.shields.io/badge/作者QQ-627577391-orange.svg)]()
[![license](https://img.shields.io/badge/license-MIT-blue.svg)]()
[![版本](https://img.shields.io/badge/version-2019.2.9-green.svg)]()


一个异步、多平台兼容的**聊天机器人**框架。

**当前正在重构，框架可能会有很大的变化，旧框架将创建old分支，重构完成后新款框架为master分支。**
**框架同时将更名为zhamao-framework(炸毛框架)，与旧版本兼容性不并存，届时需要根据文档进行模块的升级。**

## CQBot-swoole 文档
本项目的文档正在努力编写中：[https://cqbot.crazywhale.org/](https://cqbot.crazywhale.org/)

## 什么是Swoole
PHP原生对多线程、多进程、异步等特性支持不是很好，有了Swoole，你可以非常简单自由地写出优雅的高性能服务器。

本项目原生支持多机器人连接，故选择了反向Websocket连接方式。同时更适用于高并发、多机器人同时连接以及对接**微信公众号**和**web前端**等场景。
[Swoole官网](https://www.swoole.com/)

## 框架简介
cqbot-swoole是一个聊天机器人框架，同时兼容酷Q（需安装[cqhttp插件](https://cqhttp.cc)），微信公众号，支持多QQ账号对接。

## 说在前面
本框架目前还有一些缺陷，说在前面的原因是如果你有更好的想法或发现的问题，可以提issue或联系作者。

> 如果你对swoole、PHP研究较深，推荐尝试学习框架的源码和运行原理后自己动手写一个。也欢迎star本项目给予支持！

## 特点
- 多账号单后端式框架
- 采用模块式编写，功能之间独立性高，可分别开关各个模块和设置响应优先级
- 全局缓存，随处使用
- 协程开发，传统同步写法实现高并发
- 除swoole外不依赖composer其他项目
- 自带HTTP、websocket服务器，可对接其他服务
- 支持协程MySQL

## 环境部署

### 酷Q和HTTP API插件
由于框架是独立于酷Q运行的，故你可以在多台主机上部署酷Q的docker。

如果你是新用户或重新安装含有HTTPAPI插件的**酷Q-Docker**的话，可以在你需要部署酷Q的Linux主机下使用下面的脚本快速构建酷Q环境，此脚本会引导进行相关的cqhttp插件设置。每台部署酷Q的主机均可直接使用下方的命令（服务器需要提前安装Docker）

```shell
#第一次部署酷Q-httpapi docker运行下面的代码
bash -c "$(wget https://raw.githubusercontent.com/crazywhalecc/cqbot-swoole/master/start-coolq.sh -O -)"

#以后每次启动/停止/重启酷Q容器执行的命令
docker start coolq
docker stop coolq
docker restart coolq

#以上指令非root用户可能需要sudo
```
### 微信公众号
很快将兼容微信公众平台，敬请期待。


## 框架部署
### 手动安装到Linux主机上
``` shell
# 安装PHP（ubuntu/debian）
apt-get install software-properties-common
add-apt-repository ppa:ondrej/php
apt-get update
apt-get install php7.2 php7.2-dev php7.2 php7.2-mbstring php7.2-json php7.2 php-pear

#安装PHP（CentOS）
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
yum makecache fast
yum install php72w-devel.x86_64 php72w-mbstring.x86_64 php72w-pear.noarch gcc gcc-c++ -y


# 安装Swoole
pecl install swoole
echo "extension=swoole.so" >> $(php -i | grep "Loaded Configuration File" | awk '{print $5}')

# 部署框架
git clone https://github.com/crazywhalecc/cqbot-swoole.git

# 以上指令可能需要sudo执行
```


### 使用Docker快速构建并启动
``` shell
sudo docker run -it --rm --net=host --name cqbot -v $(pwd)/cqbot/:/root/ jesse2061/cqbot-swoole

# 可以将命令添加为alias方便以后快速启动
echo "alias cqbot='sudo docker run -it --rm --net=host --name cqbot -v $(pwd)/cqbot/:/root/ jesse2061/cqbot-swoole'" >> ~/.bash_profile
source ~/.bash_profile
cqbot
```


## 启动
#### 直接安装后启动框架

```shell
cd cqbot-swoole/
php start.php
```

#### 在screen中运行框架

```shell
screen -R cqbot
cd cqbot-swoole/
php start.php
# Ctrl A + D 将screen放到后台运行
```

#### 使用Docker在screen中运行框架

```shell
screen -R cqbot
sudo docker run -it --rm --net=host --name cqbot -v $(pwd)/cqbot/:/root/ jesse2061/cqbot-swoole
# Ctrl A + D 将screen放到后台运行
```

## MacOS与Windows兼容性
#### MacOS下运行cqbot-swoole
mac下运行和Linux整体相同，使用brew安装好PHP后通过源码编译`swoole`组件安装，或使用docker。
> Docker for mac 运行需要手动指定端口`-p 20000:20000`，不能使用`--net=host`网络模式。

#### Windows下运行cqbot-swoole
因为swoole使用了Linux的特性，故**不推荐**在Windows电脑或服务器使用，Windows可以使用Docker运行，使用 `cygwin` 环境或 `WSL` 环境。
> 不推荐原因有不能使用`reload`指令进行重启服务和不能使用全部的swoole特性。

## 关于

框架和SDK部分代码直接从 [炸毛机器人](https://github.com/zhamao-robot/) 中移植而来，炸毛机器人（3290004669）是作者写的一个高性能的机器人，曾获全国计算机设计大赛一等奖。

欢迎随时在HTTP-API插件群提问，当然更好的话可以加作者QQ（627577391）或提交issue进行疑难解答。

本项目在有更新内容时，请及时关注GitHub的动态，更新前请将自己的**模块**代码做好备份。
