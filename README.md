<div align="center">
  <img src="/resources/images/logo_trans.png" height = "150" alt="炸毛框架"><br>
  <h2>炸毛框架</h2>
  炸毛框架 (zhamao-framework) 是一个协程高性能的聊天机器人 + Web 服务器开发框架<br><br>

[![作者QQ](https://img.shields.io/badge/作者QQ-627577391-orange.svg)]()
[![zhamao License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/zhamao-robot/zhamao-framework/blob/master/LICENSE)
[![Latest Stable Version](http://img.shields.io/packagist/v/zhamao/framework.svg)](https://packagist.org/packages/zhamao/framework)
[![Banner](https://img.shields.io/badge/CQHTTP-v11-black)]()

[![注解数量](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/AnnotationBase.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=AnnotationBase)
[![TODO 数量](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/TODO.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=TODO)

</div>

## 开发者注意
开发者 QQ 群：**670821194**

当前 v2 版本已正式发布，此 master 分支为 2.0 版本，如需查看 v1 版本，请移步 `v1-legacy` 分支！

2.0 版本如果有问题请第一时间加群反馈！

## 简介
炸毛框架使用 PHP 编写，采用 Swoole 扩展为基础，主要面向 API 服务，聊天机器人（OneBot 兼容的 QQ 机器人对接），包含 Websocket、HTTP 等监听和请求库，用户代码采用模块化处理，使用注解可以方便地编写各类功能。

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

## 开始
框架首先需要部署环境，可以参考下方文档中部署环境和框架的方法进行。

## 文档（v2 版本）
查看文档：[https://docs-v2.zhamao.xin/](https://docs-v2.zhamao.xin/)

备用链接：[https://docs-v2.zhamao.me/](https://docs-v2.zhamao.me/)

自行构建文档：`mkdocs build -d distribute`

## 特点
- 原生为多账号设计，支持多个机器人负载均衡
- 使用 Swoole 多工作进程机制和协程加持，尽可能简单的情况下提升了性能
- 灵活的注解事件绑定机制
- 易用的上下文，模块内随处可用
- 采用模块化编写，可自由搭配其他 composer 组件
- 常驻内存，全局缓存变量随处使用，提供多种缓存方案
- 自带 MySQL、Redis 等数据库连接池等数据库连接方案
- 本身为 HTTP 服务器、WebSocket 服务器，可以构建属于自己的 HTTP API 接口
- 静态文件服务器，可将前端合并到一起

## 从 v1 升级
炸毛框架 v2 相对 v1 版本改动了不少内容，其中包括框架底层机制、注解事件分发、调试、命名空间等变化，详情可查看上方文档。

如果旧版框架使用过程中无问题且对新功能暂无需求，可以继续使用 v1 版本，后续也将维护安全类更新和修复致命 bug。

## 贡献和捐赠
如果你在使用过程中发现任何问题，可以提交 Issue 或自行 Fork 后修改并提交 Pull Request。

目前项目仅一人维护，耗费精力较大，所以非常欢迎对框架的贡献。

本项目为作者闲暇时间开发，如果觉得好用，不妨进行捐助～你的捐助会让我更加有动力完善插件，感谢你的支持！

我们会将捐赠的资金用于本项目驱动的炸毛机器人和框架文档的服务器开销上。

### 支付宝
![支付宝二维码](/resources/images/alipay_img.jpg)

如果你对我们的周边感兴趣，我们还有炸毛机器人定制 logo 的雨伞，详情咨询作者 QQ，我们会作为您捐助了本项目！

## 关于
框架和 SDK 是 炸毛机器人 项目的核心框架开源部分。炸毛机器人是作者写的一个高性能机器人，曾获全国计算机设计大赛一等奖。

作者的炸毛机器人已从2018年初起稳定运行了**三年**，并且持续迭代。

欢迎随时在 HTTP-API 插件群里提问，当然更好的话可以加作者 QQ（627577391）或提交 Issue 进行疑难解答。

本项目在更新内容时，请及时关注 GitHub 动态，更新前请将自己的模块代码做好备份。

项目框架采用 Apache-2.0 协议开源，在分发或重写修改等操作时需遵守协议。项目模块部分(`Module` 文件夹) 在非借鉴框架内代码时可不遵守 Apache-2.0 协议进行分发和修改(声明版权)。

**注意**：在你使用 mirai 等 `AGPL-3.0` 协议的机器人软件与框架连接时，使用本框架需要将你编写或修改的部分使用 `AGPL-3.0` 协议重新分发。

在贡献代码时，请保管好自己的全局配置文件中的敏感信息，请勿将带有个人信息的配置文件上传 GitHub 等网站。

![star](https://starchart.cc/zhamao-robot/zhamao-framework.svg)
