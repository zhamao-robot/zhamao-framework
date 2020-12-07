<div align="center">
  <img src="/resources/images/logo_trans.png" height = "150" alt="炸毛框架"><br>
  <h2>炸毛框架</h2>
  炸毛框架 (zhamao-frameowork) 是一个协程高性能的聊天机器人 + Web 服务器开发框架<br><br>

[![作者QQ](https://img.shields.io/badge/作者QQ-627577391-orange.svg)]()
[![zhamao License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/zhamao-robot/zhamao-framework/blob/master/LICENSE)
[![Latest Stable Version](http://img.shields.io/packagist/v/zhamao/framework.svg)](https://packagist.org/packages/zhamao/framework)
[![Banner](https://img.shields.io/badge/CQHTTP-v11-black)]()
[![dev-version](https://img.shields.io/badge/dev--version-v2.0.0--a1-green)]()

[![stupid counter](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/stupid.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=stupid)
[![TODO counter](https://img.shields.io/github/search/zhamao-robot/zhamao-framework/TODO.svg)](https://github.com/zhamao-robot/zhamao-framework/search?q=TODO)

 </div>

## 开发者注意
**v2.0 版本已经开始公测了，但是文档还在光速编写中，可以现行进行测试！**

**炸毛框架目前经过实验可以直接在 PHP8 环境上运行，但是细节部分未经充分测试，如果在 PHP8 环境下运行出现问题，请及时提出 Issue，谢谢！**

**由于 CQHTTP 不再提供维护，转为 [OneBot 标准](https://github.com/howmanybots/onebot)（原 CQHTTP 插件衍生而来的机器人 HTTP 接口标准），本框架也将在未来改为兼容此标准。**

**以上涉及的变更将在下一个大版本 (v2.0.0) 更新，请关注 2.0-dev 分支 和 Project 模块！**

**v2.0版本即将到来，请持续关注 [新文档](https://docs-v2.zhamao.xin/) 进度和 Project 模块展示的测试进度！**

## 简介
zhamao-framework 是一个 PHP Swoole 的聊天机器人框架，兼容 OneBot 标准，它会对微信公众号等终端收到的消息进行解析处理，并以模块化的形式进行开发，来完成机器人的自然语言对话等功能。

除了起到解析消息的作用，炸毛框架 还提供了完整的 WebSocket + HTTP 服务器，你还能用此框架构建出高性能的 API 接口服务器。

## 开始
先安装环境，环境安装见下方文档。
1. `composer create-project zhamao/framework-starter` 从模板新建基础文档结构进行使用
2. 你也可以直接拉取本项目，进入文件夹后 `composer update` 加载依赖后使用 `bin/start init` 快速初始化框架文件
3. 还可以使用 Dockerfile 构建 Docker 容器

## 文档 (v1.x)
国内服务器：[https://docs-v1.zhamao.xin/](https://docs-v1.zhamao.xin/)
GitHub Pages：[https://docs-v1.zhamao.me/](https://docs-v1.zhamao.me/)
## 特点
- 支持多账号
- 灵活的注解事件绑定机制
- 支持下断点调试（Psysh）
- 易用的上下文，模块内随处可用
- 采用模块化编写，功能之间高内聚低耦合
- 常驻内存，全局缓存变量随处使用
- 自带 MySQL 查询器、数据库连接池等数据库连接方案
- 自带 HTTP 服务器、WebSocket 服务器可复用，可以构建属于自己的 HTTP API 接口
- 静态文件服务器
- 支持 phar 一键打包

## 炸毛特色模块

| 模块名称           | 说明                             | 模块地址                                                     |
| ------------------ | -------------------------------- | ------------------------------------------------------------ |
| 通用模块 | 图片上传和下载模块 | [zhamao-general-tools](https://github.com/zhamao-robot/zhamao-general-tools) |

## 计划开发内容
- [X] WebSocket测试脚本（客户端）
- [X] Session 和中间层管理模块
- [X] 常驻服务脚本
- [X] 一些常用的通用 API 例如经济（用户积分、亲密度等）的模块
- [ ] 图灵机器人/腾讯AI 聊天模块
- [ ] 分词模块（可能会放弃计划，因为目前好用的分词都是其他语言的）
- [ ] HTTP 过滤器、Auth 模块、完整的 MVC 兼容（可能会放弃计划，因为框架主打机器人开发）
- [ ] Redis 连接池或开箱即用的相应功能内置
- [X] 1.3 版本使用上下文代替
- [X] 更好的 Logger，稳定和漂亮的控制台输出
- [ ] 日志服务
- [X] 框架支持 Phar 打包（可能会比较靠后支持）
- [ ] 完整的单元测试（如果有需求则尽快开发）
- [X] 静态文件服务器

## 从 cqbot-swoole 升级
目前新的框架采用了全新的注解机制，所以旧版的框架上写的模块到新框架需要重新编写。当然为了减少工作量，新的框架也最大限度地保留了旧版框架编写的风格，一般情况下根据新版框架的文档仅需修改少量地方即可完成重写。

旧版框架并入了 `old` 分支，如果想继续使用旧版框架请移步分支。升级过程中如果遇到问题可以找作者。

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
