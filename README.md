<div align="center">
  <img src="https://cdn.jsdelivr.net/gh/zhamao-robot/zhamao-framework/resources/images/logo_trans.png" width = "150" height = "150" alt="炸毛框架"><br>
  <h2>炸毛框架</h2>
  炸毛框架 (zhamao-framework) 是一个高性能的聊天机器人 + Web 服务器开发框架<br><br>

<p align="center">
  <a href="https://onebot.dev/">
    <img src="https://img.shields.io/badge/OneBot-12-black?style=flat-square" alt="OneBot">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/actions">
    <img src="https://img.shields.io/github/actions/workflow/status/zhamao-robot/zhamao-framework/test.yml?branch=v3-develop&label=Test&style=flat-square" alt="Integration Test">
  </a>

  <a href="https://packagist.org/packages/zhamao/framework">
    <img src="https://img.shields.io/packagist/dt/zhamao/framework?label=Downloads&style=flat-square" alt="下载数">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/releases">
    <img src="https://img.shields.io/packagist/v/zhamao/framework?include_prereleases&label=Release&style=flat-square" alt="最新版本">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/blob/master/LICENSE">
    <img src="https://img.shields.io/github/license/zhamao-robot/zhamao-framework?label=License&style=flat-square" alt="开源协议">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/search?q=TODO">
    <img src="https://img.shields.io/github/search/zhamao-robot/zhamao-framework/TODO?label=TODO%20Counter&style=flat-square" alt="TODO">
  </a>
</p>

</div>

开发者 QQ 群：**670821194** [点击加入群聊](https://jq.qq.com/?_wv=1027&k=YkNI3AIr)

**如果有愿意一起开发框架本身的开发者，请提出 PR 或 Issue 参与开发！如果对框架本身的核心设计有更好的想法，可与作者成立开发组（目前仅 2 人），参与 OneBot V12 生态和框架本身的开发。**

**相关正在进行的版本任务见 Projects 一栏！**

## 简介

炸毛框架使用 PHP 编写，主要面向 API 服务，聊天机器人，包含 Websocket、HTTP
等监听和请求库，用户代码采用模块化处理，使用注解可以方便地编写各类功能。

框架主要用途为 HTTP 服务器，机器人搭建框架。尤其对于聊天机器人消息处理较为方便和全面，提供了众多会话机制和内部调用机制，可以以各种方式设计你自己的插件。

```php
#[\BotCommand('你好')]
public function hello(\BotContext $ctx) {
  $ctx->reply("你好，我是炸毛！"); // 简单的命令式回复
}
#[\Route('/index')]
public function index() {
  return "<h1>hello!</h1>"; // 快速的 HTTP 服务开发
}
```

## 开始

框架目前支持 Linux、WSL、macOS、Windows 环境直接运行，其中 Linux、macOS 环境可直接使用下方一键安装脚本。

> 如果你想在其他环境安装部署，可使用 Docker 快速部署或手动安装环境后安装框架，详见文档。

```bash
# Linux、macOS 下一键检测 PHP 环境、安装框架
bash <(curl -fsSL https://zhamao.xin/v3.sh)

# 启动框架
cd zhamao-v3
./zhamao server
```

一键安装脚本还有可以自定义参数的方式，比如：

```bash
# 脚本默认会检测系统的PHP，如果想直接跳过检测，安装独立的PHP版本，则添加此环境变量
export ZM_NO_LOCAL_PHP="yes"
# 脚本如果安装独立版本PHP，默认版本为8.1，如果想使用其他版本，则添加此环境变量指定版本
export ZM_DOWN_PHP_VERSION="8.2"
# 脚本默认会将框架在当前目录下的 `zhamao-app` 目录进行安装，如果想使用其他目录，则添加此环境变量
export ZM_CUSTOM_DIR="my-custom-app"
# 脚本默认会对本项目使用阿里云国内加速镜像，如果想使用packagist源，则添加此环境变量
export ZM_COMPOSER_PACKAGIST="yes"
# 执行完前面的环境变量再执行一键安装脚本，就可以实现自定义参数！
bash <(curl -fsSL https://zhamao.xin/v3.sh)
```

关于其他安装方式，请参阅 [文档](https://framework.zhamao.xin/guide/installation.html) 。

## 文档

查看文档（国内自建）：<https://framework.zhamao.xin/>

备用链接（国外托管）：<https://framework.zhamao.me/>

## 特点

- 原生支持多个机器人客户端同时连接
- 灵活的注解事件绑定机制，可同时使用 Annotation 和原生 Attribute 注解
- 完善的插件系统，可编写插件后打包或分发，供他人使用
- 采用插件化编写，可自由搭配其他 Composer 组件，也可单文件面向过程编写
- 支持模块打包、热加载，分享模块更方便
- 常驻内存，全局缓存变量随处使用，提供多种缓存方案
- 自带 MySQL、SQLite、Redis 等数据库连接池
- 本身为 HTTP 服务器、WebSocket 服务器，可以构建属于自己的 HTTP API 接口
- 可选自带 PHP 环境，无需手动编译安装，by [crazywhalecc/static-php-cli](https://github.com/crazywhalecc/static-php-cli)

## 贡献和捐赠

如果你在使用过程中发现任何问题，可以提交 Issue 或自行 Fork 后修改并提交 Pull Request。

目前项目仅两人维护，耗费精力较大，所以非常欢迎对框架的贡献。

本项目为作者闲暇时间开发，如果觉得好用，不妨进行捐助～你的捐助会让我更加有动力完善插件，感谢你的支持！

我们会将捐赠的资金用于本项目驱动的炸毛机器人和框架文档的服务器开销上。[捐赠列表](https://github.com/zhamao-robot/thanks)

如果您不想直接参与框架的开发，也可以分享你编写的模块，帮助完善框架生态。

### 支付宝

![支付宝二维码](https://cdn.jsdelivr.net/gh/zhamao-robot/zhamao-framework/resources/images/alipay_img.jpg)

## 关于

框架和 SDK 是 炸毛机器人 项目的核心框架开源部分。炸毛机器人是作者写的一个高性能机器人，曾获全国计算机设计大赛一等奖。

作者的炸毛机器人已从2018年初起稳定运行了**七年**，并且持续迭代。

可以提交 [Issue](https://github.com/zhamao-robot/zhamao-framework/issues/new/choose) 或 [加群(670821194)](https://jq.qq.com/?_wv=1027&k=YkNI3AIr) 进行疑难解答。

本项目在更新内容时，请及时关注 GitHub 动态，更新前请将自己的插件或项目代码做好备份。

项目框架采用 Apache-2.0 协议开源，在分发或重写修改等操作时需遵守协议。项目插件部分(除 `src/Globals`、`src/ZM` 文件夹外的其他文件夹) 在非借鉴框架内代码时可不遵守 Apache-2.0 协议进行分发和修改(声明版权)。

**注意**：在你使用 mirai 等 `AGPL-3.0` 协议的机器人软件与框架连接时，使用本框架需要将你编写或修改的部分使用 `AGPL-3.0` 协议重新分发。

在贡献代码时，请保管好自己的全局配置文件中的敏感信息，请勿将带有个人信息的配置文件上传 GitHub 等网站。

感谢 JetBrains 为此开源项目提供 PhpStorm 开发工具支持：

<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/PhpStorm.svg" width="300">

感谢开发者 @sunxyw 中为项目开发规范化提出的一些建议。

![star](https://starchart.cc/zhamao-robot/zhamao-framework.svg)
