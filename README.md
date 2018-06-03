# CQBot-swoole
A fast and multi-task framework for Coolq-HTTP-API<br>
一个快速、多进程的<a href="https://cqhttp.cc/">CQ-HTTP-API插件</a>框架 made by php-swoole
<br>
<br>
[![作者QQ](https://img.shields.io/badge/作者QQ-627577391-orange.svg)]()<br>
<h2>什么是swoole</h2>
Swoole是一个C编写的、高性能的PHP扩展。支持多线程、多进程、同步、异步、协程、SQL等。<br>
Swoole的用处最简单可以理解为，只需要简单几行代码即可运行一个HTTP服务器，比python等同类型解释型语言快非常多。<br>
此外小声说Swoole官方声明Swoole将重新定义PHP<br>
<a href="https://swoole.com/">官网点我</a><br>
<br>
<br>
<br>
<h2>框架简介</h2>
· CQBot-swoole采用swoole框架为基础，在框架上以PHP作为开发语言进行编写。<br>
· CQBot采用常驻内存+websocket的方式，最大程度地提升了CQHTTP插件处理消息事件的速度<br>
· 框架采用快速开发的模板，可以在非常短的时间内添加一个功能<br>
· 框架保留了微信公众号接口，后期我会添加和微信公众号的互通<br>
· 框架本身比较大，更适用于处理消息量特别大、功能比较复杂的机器人<br>
· 框架自带HTTP服务器，可以同时使用HTTP服务器完成相关业务逻辑<br>
· 框架包含一些基础的功能，当然目前还正在不断添加中<br>
· 支持外置扩展加载（邮件收发、Excel、Word文件处理等）<br>
<br>
<br>
<br>
<h2>环境需求</h2>
1. Linux（由于酷Q是基于Windows版的，所以这里推荐搭配docker版）<br>
2. php7.0以上<br>
3. php-mbstring（多字节扩展）<br>
4. swoole-2.x<br>
5. screen（可选）<br>
6. CQ-http-api插件和酷Q（docker版）
<br>
<br>
<br>
<h2>环境部署</h2>
<h3>安装docker-coolq</h3>
如果你想重新开始，则运行安装richardchien的docker镜像快速安装即可
<pre>$ docker pull richardchien/cqhttp:latest
$ git clone https://github.com/BlueWhaleNetwork/CQBot-swoole.git
$ mkdir coolq  # 用于存储酷 Q 的程序文件

#如果你想将docker运行在screen里，输入下面的指令
$ screen -R coolq 

#将启动脚本移到和coolq目录平级的文件夹（可选）
$ mv CQBot-swoole/run.sh ./

#启动docker的指令
$ sh ./run.sh</pre>
docker启动后打开浏览器，输入http://你的服务器地址:9000<br>
默认密码为MAX8char，此后登陆QQ即可<br>
<br>
<br>
<br>
<h3>Framework部署</h3>
由于Framework是基于swoole和php7的，所以需要先安装php7和swoole扩展<br>
由于本教程不是PHP安装教程，所以仅简单介绍PHP安装，具体安装步骤可以自己google一下
<h4>1. Debian系安装PHP</h4>
<pre>#安装PHP
$ sudo apt-get install php php-dev php-mbstring php7

#测试php安装是否成功
$ php -v</pre>
<h4>2. 安装swoole扩展</h4>
<pre>#从github拉取
$ git clone https://github.com/swoole/swoole-src.git
$ cd swoole-src
$ phpize
$ ./configure
$ make && make install</pre>
<h4>3. 编辑php.ini文件添加swoole</h4>
<pre>#找到php.ini文件
#一般在/etc/php.ini或者/etc/php/7.0/cli/php.ini或其他地方
$ php -i # 可以查看php.ini位置

#修改php.ini，添加swoole.so到扩展列表里

#if使用vim编辑
$ sudo vim /etc/php.ini

#找到extension=xxxx.dll一堆那个地方
#插入一行：extension=swoole.so
#然后就成功安装了swoole扩展！</pre>
<h4>4. 运行框架</h4>
<pre>#框架可运行在screen，方便驻留后端并查看log
$ screen -R fw

#运行框架
$ cd CQBot-swoole/
$ php start.php</pre>
<h4>5. 修改自己的参数</h4>
你可以进入start.php文件中，修改自己的机器人管理群、管理员QQ号、错误日志显示等级等功能。
<br><br>
当然，由于swoole和php的安装步骤较为复杂，我会抓紧制作CQBot-swoole的docker镜像，此后可以直接使用docker启动框架
<br>
这个框架作者在使用，从炸毛机器人的框架中移植。<br>

<p>
获得过：<br>
计算机应用能力大赛二等奖<br>
溢达全国创意大赛一等奖<br>
</p>
如有任何问题可以随时戳死作者哦
