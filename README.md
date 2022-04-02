<div align="center">
  <img src="https://cdn.jsdelivr.net/gh/zhamao-robot/zhamao-framework/resources/images/logo_trans.png" width = "150" height = "150" alt="炸毛框架"><br>
  <h2>炸毛框架</h2>
  炸毛框架 (zhamao-framework) 是一个协程高性能的聊天机器人 + Web 服务器开发框架<br><br>

<p align="center">
  <a href="https://onebot.dev/">
    <img src="https://img.shields.io/badge/OneBot-11-black?style=flat-square" alt="OneBot">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/actions">
    <img src="https://img.shields.io/github/workflow/status/zhamao-robot/zhamao-framework/Integration%20and%20Style%20Test?label=Test&style=flat-square" alt="Integration Test">
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

  <a href="https://wpa.qq.com/msgrd?v=3&uin=627577391&site=qq&menu=yes">
    <img src="https://img.shields.io/badge/作者QQ-627577391-orange?style=flat-square" alt="作者QQ">
  </a>

  <br>
  <br>

  <a href="https://github.com/zhamao-robot/zhamao-framework/search?q=AnnotationBase">
    <img src="https://img.shields.io/github/search/zhamao-robot/zhamao-framework/AnnotationBase?label=Annotations&style=flat-square" alt="注解数量">
  </a>

  <a href="https://github.com/zhamao-robot/zhamao-framework/search?q=TODO">
    <img src="https://img.shields.io/github/search/zhamao-robot/zhamao-framework/TODO?label=TODO&style=flat-square" alt="TODO">
  </a>
</p>

</div>

开发者 QQ 群：**670821194** [点击加入群聊](https://jq.qq.com/?_wv=1027&k=YkNI3AIr)

**如果有愿意一起开发框架本身的开发者，请提出 PR 或 Issue 参与开发！如果对框架本身的核心设计有更好的想法，可与作者成立开发组（目前仅 2 人），参与 OneBot V12 生态和框架本身的开发。**

**相关正在进行的版本任务见 Projects 一栏！**

## 简介

炸毛框架使用 PHP 编写，采用 Swoole 扩展为基础，主要面向 API 服务，聊天机器人（OneBot 兼容的 QQ 机器人对接），包含 Websocket、HTTP
等监听和请求库，用户代码采用模块化处理，使用注解可以方便地编写各类功能。

框架主要用途为 HTTP 服务器，机器人搭建框架。尤其对于 QQ 机器人消息处理较为方便和全面，提供了众多会话机制和内部调用机制，可以以各种方式设计你自己的模块。

```php
/**
 * @CQCommand("你好")
 */
public function hello() {
  ctx()->reply("你好，我是炸毛！"); // 简单的命令式回复
}
/**
 * @RequestMapping("/index")
 */
public function index() {
  return "<h1>hello!</h1>"; // 快速的 HTTP 服务开发
}
```

> 从 2.7.0 版本开始，框架已支持同时使用 Annotation 和原生 Attribute 注解，供开发者根据需要自由选用。

## 开始

如果你是初学者，可以直接使用以下脚本部署 PHP 环境和安装框架的脚手架：

```bash
# 新建一个自己喜欢名字的文件夹，运行一键安装脚本 (仅限 x86-64(AMD64) 和 AArch64(ARM64) 平台)
mkdir zhamao-app/
cd zhamao-app/
# 默认安装的 PHP 版本为 7.4，如需使用其他版本，请设置环境变量 ZM_DOWN_PHP_VERSION 为对应的 PHP 版本，例如：
# export ZM_DOWN_PHP_VERSION=8.1
bash -c "$(curl -fsSL https://api.zhamao.xin/go.sh)"

# 启动
./zhamao server:start
```

关于其他安装方式，请参阅[文档](https://framework.zhamao.xin/guide/installation.html) 。

## 文档

查看文档（国内自建）：<https://framework.zhamao.xin/>

备用链接（国外托管）：<https://framework.zhamao.me/>

## 特点

- 原生支持多个机器人客户端同时连接
- 使用 Swoole 多工作进程机制和协程加持，尽可能简单的情况下提升了性能
- 灵活的注解事件绑定机制，可同时使用 Annotation 和原生 Attribute 注解
- 易用的上下文，模块内随处可用
- 采用模块化编写，可自由搭配其他 Composer 组件，也可单文件面向过程编写
- 支持模块打包、热加载，分享模块更方便
- 常驻内存，全局缓存变量随处使用，提供多种缓存方案
- 自带 MySQL、Redis 等数据库连接池等数据库连接方案
- 本身为 HTTP 服务器、WebSocket 服务器，可以构建属于自己的 HTTP API 接口
- 静态文件服务器，可将前端合并到一起
- 自带 PHP + Swoole 环境，无需手动编译安装，by [crazywhalecc/static-php-cli](https://github.com/crazywhalecc/static-php-cli)

## 下载源码

框架源码可直接克隆本仓库进行编辑，如果你在国内，访问 GitHub 和克隆仓库比较慢，可以将 `github.com` 替换为 `fgit.zhamao.me` 进行加速。

例如：`git clone https://fgit.zhamao.me/zhamao-robot/zhamao-framework.git --depth 1`。

## 贡献和捐赠

如果你在使用过程中发现任何问题，可以提交 Issue 或自行 Fork 后修改并提交 Pull Request。

目前项目仅一人维护，耗费精力较大，所以非常欢迎对框架的贡献。

本项目为作者闲暇时间开发，如果觉得好用，不妨进行捐助～你的捐助会让我更加有动力完善插件，感谢你的支持！

我们会将捐赠的资金用于本项目驱动的炸毛机器人和框架文档的服务器开销上。[捐赠列表](https://github.com/zhamao-robot/thanks)

如果您不想直接参与框架的开发，也可以分享你编写的模块，帮助完善框架生态。

### 支付宝

![支付宝二维码](https://cdn.jsdelivr.net/gh/zhamao-robot/zhamao-framework/resources/images/alipay_img.jpg)

## 关于

框架和 SDK 是 炸毛机器人 项目的核心框架开源部分。炸毛机器人是作者写的一个高性能机器人，曾获全国计算机设计大赛一等奖。

作者的炸毛机器人已从2018年初起稳定运行了**四年半**，并且持续迭代。

欢迎随时在 HTTP-API 插件群里提问，当然更好的话可以加作者 QQ（[627577391](http://wpa.qq.com/msgrd?v=3&uin=627577391&site=qq&menu=yes)）
或提交 [Issue](https://github.com/zhamao-robot/zhamao-framework/issues/new/choose) 进行疑难解答。

本项目在更新内容时，请及时关注 GitHub 动态，更新前请将自己的模块代码做好备份。

项目框架采用 Apache-2.0 协议开源，在分发或重写修改等操作时需遵守协议。项目模块部分(`Module` 文件夹) 在非借鉴框架内代码时可不遵守 Apache-2.0 协议进行分发和修改(声明版权)。

**注意**：在你使用 mirai 等 `AGPL-3.0` 协议的机器人软件与框架连接时，使用本框架需要将你编写或修改的部分使用 `AGPL-3.0` 协议重新分发。

在贡献代码时，请保管好自己的全局配置文件中的敏感信息，请勿将带有个人信息的配置文件上传 GitHub 等网站。

感谢 JetBrains 为此开源项目提供 PhpStorm 开发工具支持：

<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/PhpStorm.svg" width="300">

感谢 [php-libonebot](https://github.com/botuniverse/php-libonebot) 开发者 @sunxyw 中为项目开发规范化提出的一些建议。

<!-- ![star](https://starchart.cc/zhamao-robot/zhamao-framework.svg) -->
