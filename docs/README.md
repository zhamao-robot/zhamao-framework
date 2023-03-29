---
home: true
heroImage: ./logo_trans.png
actions:
  - text: 快速上手
    link: /guide/
    type: primary
    size: large
  - text: 插件市场
    link: /plugins/market
    type: primary
    ghost: true
    size: large
features:
- title: 高性能
  details: 使用基于 PHP 的高性能扩展库，利用 OneBot 协议实现与聊天机器人软件的通信，还有数据库连接池、内存缓存、多任务进程等特色，大幅增强性能。
- title: 易于开发
  details: 所有功能采用插件化设计，除特殊情况外几乎所有功能都不需要修改框架内任意代码，框架自带插件的安装、打包、解包功能，方便分发和管理。
- title: 接口直观
  details: 支持命令、普通文本、正则匹配、自然语言处理等多种对话解析方式，利用协程巧妙实现了直观的交互式会话模式，同时支持多种富文本的处理。
footer: |
  Apache-2.0 Licensed &nbsp;|&nbsp; Copyright © 2019-2023 CrazyBot Team &nbsp;|&nbsp; <a href="http://beian.miit.gov.cn">沪ICP备2021010446号-1</a>
---

# 快速上手

## 安装框架和环境

此命令可一键以模板安装框架！（仅限 Linux 和 macOS）

```bash
bash <(curl -fsSL https://zhamao.xin/v3.sh)
```

## 运行框架

```bash
cd zhamao-v3/
./zhamao server
```

## 效果图

![index_demo](https://img.zhamao.xin/framework/framework-demo.png)
