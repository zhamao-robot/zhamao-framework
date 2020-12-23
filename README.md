<div align="center">
  <img src="/resources/images/logo_trans.png" height = "150" alt="炸毛框架"><br>
  <h2>炸毛框架</h2>
  炸毛框架 (zhamao-framework) 是一个协程高性能的聊天机器人 + Web 服务器开发框架<br><br>

[![作者QQ](https://img.shields.io/badge/作者QQ-627577391-orange.svg)]()
[![zhamao License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/zhamao-robot/zhamao-framework/blob/master/LICENSE)
[![Latest Stable Version](http://img.shields.io/packagist/v/zhamao/framework.svg)](https://packagist.org/packages/zhamao/framework)
[![Banner](https://img.shields.io/badge/CQHTTP-v11-black)]()
[![dev-version](https://img.shields.io/badge/dev--version-v2.0.0--a1-green)]()

[![stupid counter](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/stupid.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=stupid)
[![TODO counter](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/TODO.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=TODO)

 </div>

## 开发者注意
**此项目终于有开发讨论 QQ 群了！群号：670821194**

**此分支为炸毛框架 v1 旧版本，不推荐新用户使用，且仅维护重要的部分和修复 bug，不加入新的内容，请移步 v2 版本，在 master 分支！**

**v2.0版本即将到来，请持续关注 [新文档](https://docs-v2.zhamao.xin/) 的更新进度！**

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

## 文档 (v1.x)
国内服务器：[https://docs-v1.zhamao.xin/](https://docs-v1.zhamao.xin/)

GitHub Pages：[https://docs-v1.zhamao.me/](https://docs-v1.zhamao.me/)

## 特点
- 多进程，性能超高
- 支持多机器人账号
- 灵活的注解事件绑定机制
- 易用的上下文，模块内随处可用
- 采用模块化编写，功能之间高内聚低耦合
- 常驻内存，全局缓存变量随处使用
- 自带 MySQL、Redis、等数据库连接方案
- 自带 HTTP 服务器、WebSocket 服务器可复用，可以构建属于自己的 HTTP API 接口
- 静态文件服务器

## 炸毛特色模块（2.0 版本下未适配）

| 模块名称           | 说明                             | 模块地址                                                     |
| ------------------ | -------------------------------- | ------------------------------------------------------------ |
| 通用模块 | 图片上传和下载模块 | [zhamao-general-tools](https://github.com/zhamao-robot/zhamao-general-tools) |

## 贡献和捐赠
如果你在使用过程中发现任何问题，可以提交 Issue 或自行 Fork 后修改并提交 Pull Request。目前项目仅一人维护，耗费精力较大，所以非常欢迎对框架的贡献。

本项目为作者闲暇时间开发，如果觉得好用，不妨进行捐助～你的捐助会让我更加有动力完善插件，感谢你的支持！

我们会将捐赠的资金用于本项目驱动的炸毛机器人和框架文档的服务器开销上。

### 支付宝
![支付宝二维码](/resources/images/alipay_img.jpg)

如果你对我们的周边感兴趣，我们还有炸毛机器人定制 logo 的雨伞，详情咨询作者 QQ，我们会作为您捐助了本项目！

## 关于
框架和 SDK 是 炸毛机器人 项目的核心框架开源部分。炸毛机器人是作者写的一个高性能机器人，曾获全国计算机设计大赛一等奖。

欢迎随时在 HTTP-API 插件群里提问，当然更好的话可以加作者 QQ（627577391）或提交 Issue 进行疑难解答。

本项目在更新内容时，请及时关注 GitHub 动态，更新前请将自己的模块代码做好备份。

项目框架采用 Apache-2.0 协议开源，在分发或重写修改等操作时需遵守协议。项目模块部分(`Module` 文件夹) 在非借鉴框架内代码时可不遵守 Apache-2.0 协议进行分发和修改(声明版权)。

**注意**：在你使用 mirai 等 `AGPL-3.0` 协议的机器人软件与框架连接时，使用本框架需要将你编写或修改的部分使用 `AGPL-3.0` 协议重新分发。

![star](https://starchart.cc/zhamao-robot/zhamao-framework.svg)
